<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PunchSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => 'sometimes|date_format:Y-m']);

        $month = $request->input('month', now()->format('Y-m'));

        $query = PunchSession::where('user_id', $request->user()->id)
            ->where('state', 'clocked_out')
            ->with('site', 'breaks')
            ->orderByDesc('clocked_in_at');

        [$year, $mon] = explode('-', $month);
        $start = Carbon::create($year, $mon, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $query->whereBetween('clocked_in_at', [$start, $end]);

        $sessions = $query->paginate(20)->withQueryString();

        // Month options: current month + 5 previous
        $monthOptions = collect(range(0, 5))->map(fn($i) => now()->subMonths($i)->format('Y-m'));

        return view('employee.history', compact('sessions', 'month', 'monthOptions'));
    }
}
