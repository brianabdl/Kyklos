<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PunchSession;
use App\Models\Shift;
use Illuminate\Http\Request;

class EmployeeShiftController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $upcoming = Shift::where('user_id', $userId)
            ->where('scheduled_start', '>=', now())
            ->with('site')
            ->orderBy('scheduled_start')
            ->limit(20)
            ->get();

        $past = Shift::where('user_id', $userId)
            ->where('scheduled_start', '<', now())
            ->with('site')
            ->orderByDesc('scheduled_start')
            ->limit(15)
            ->get();

        // Attach completion status to each past shift
        $completedShiftIds = PunchSession::where('user_id', $userId)
            ->where('state', 'clocked_out')
            ->whereIn('shift_id', $past->pluck('id'))
            ->pluck('shift_id')
            ->flip();

        $past = $past->map(function ($shift) use ($completedShiftIds) {
            $shift->status = $completedShiftIds->has($shift->id) ? 'completed' : 'missed';
            return $shift;
        });

        return view('employee.shifts', compact('upcoming', 'past'));
    }
}
