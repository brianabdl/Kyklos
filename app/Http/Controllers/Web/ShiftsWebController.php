<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftsWebController extends Controller
{
    public function index(Request $request)
    {
        $orgId = auth()->user()->org_id;
        $week  = $request->query('week', now()->startOfWeek()->toDateString());
        $start = Carbon::parse($week)->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        $shifts = Shift::where('org_id', $orgId)
            ->whereBetween('scheduled_start', [$start, $end])
            ->with('user', 'site')
            ->orderBy('scheduled_start')
            ->get();

        $employees = User::where('org_id', $orgId)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $sites = Site::where('org_id', $orgId)->orderBy('name')->get();

        // Map: [userId][dayIndex] => shift
        $days = collect(range(0, 6))->map(fn($i) => $start->copy()->addDays($i));
        $schedule = [];
        foreach ($employees as $employee) {
            foreach ($days as $i => $day) {
                $schedule[$employee->id][$i] = $shifts->first(
                    fn($s) => $s->user_id === $employee->id
                        && Carbon::parse($s->scheduled_start)->isSameDay($day)
                );
            }
        }

        $prevWeek = $start->copy()->subWeek()->toDateString();
        $nextWeek = $start->copy()->addWeek()->toDateString();

        return view('dashboard.shifts', compact(
            'employees', 'days', 'schedule', 'sites',
            'start', 'end', 'week', 'prevWeek', 'nextWeek'
        ));
    }
}
