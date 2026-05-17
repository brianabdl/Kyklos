<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $week = $request->query('week', now()->startOfWeek()->toDateString());
        $start = \Carbon\Carbon::parse($week)->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        $shifts = Shift::where('user_id', $request->user()->id)
            ->whereBetween('scheduled_start', [$start, $end])
            ->with('site')
            ->orderBy('scheduled_start')
            ->get();

        return response()->json(['shifts' => ShiftResource::collection($shifts)]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $shift = Shift::where('user_id', $request->user()->id)
            ->where('scheduled_start', '>', now())
            ->with('site')
            ->orderBy('scheduled_start')
            ->first();

        if (!$shift) {
            return response()->json(['shift' => null]);
        }

        return response()->json(['shift' => ShiftResource::make($shift)]);
    }
}
