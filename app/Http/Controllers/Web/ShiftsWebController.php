<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'employees', 'days', 'schedule', 'sites', 'shifts',
            'start', 'end', 'week', 'prevWeek', 'nextWeek'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|uuid',
            'site_id'          => 'required|uuid',
            'scheduled_start'  => 'required|date',
            'scheduled_end'    => 'required|date|after:scheduled_start',
            'label'            => 'nullable|string|max:255',
        ]);

        $orgId = Auth::user()->org_id;

        Shift::create([
            'org_id'          => $orgId,
            'user_id'         => $request->user_id,
            'site_id'         => $request->site_id,
            'scheduled_start' => $request->scheduled_start,
            'scheduled_end'   => $request->scheduled_end,
            'label'           => $request->label,
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('dashboard.shifts', ['week' => $request->input('week', now()->startOfWeek()->toDateString())])
            ->with('success', 'Shift created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'user_id'         => 'required|uuid',
            'site_id'         => 'required|uuid',
            'scheduled_start' => 'required|date',
            'scheduled_end'   => 'required|date|after:scheduled_start',
            'label'           => 'nullable|string|max:255',
        ]);

        $shift = Shift::where('id', $id)->where('org_id', Auth::user()->org_id)->firstOrFail();
        $shift->update($request->only('user_id', 'site_id', 'scheduled_start', 'scheduled_end', 'label'));

        return redirect()->route('dashboard.shifts', ['week' => $request->input('week', now()->startOfWeek()->toDateString())])
            ->with('success', 'Shift updated.');
    }

    public function destroy(Request $request, string $id)
    {
        Shift::where('id', $id)->where('org_id', Auth::user()->org_id)->firstOrFail()->delete();

        return redirect()->route('dashboard.shifts', ['week' => $request->input('week', now()->startOfWeek()->toDateString())])
            ->with('success', 'Shift deleted.');
    }
}
