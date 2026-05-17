<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $week  = $request->query('week', now()->startOfWeek()->toDateString());
        $start = \Carbon\Carbon::parse($week)->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        $query = Shift::where('org_id', $request->org_id)
            ->whereBetween('scheduled_start', [$start, $end])
            ->with('user', 'site');

        if ($siteId = $request->query('siteId')) {
            $query->where('site_id', $siteId);
        }

        return response()->json(['shifts' => ShiftResource::collection($query->orderBy('scheduled_start')->get())]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'userId'         => 'required|uuid',
            'siteId'         => 'required|uuid',
            'scheduledStart' => 'required|date',
            'scheduledEnd'   => 'required|date|after:scheduledStart',
            'label'          => 'nullable|string|max:255',
        ]);

        $shift = Shift::create([
            'org_id'          => $request->org_id,
            'user_id'         => $request->userId,
            'site_id'         => $request->siteId,
            'scheduled_start' => $request->scheduledStart,
            'scheduled_end'   => $request->scheduledEnd,
            'label'           => $request->label,
            'created_by'      => $request->user()->id,
        ]);

        return response()->json(['shift' => ShiftResource::make($shift->load('site'))], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'scheduledStart' => 'sometimes|date',
            'scheduledEnd'   => 'sometimes|date',
            'label'          => 'nullable|string|max:255',
        ]);

        $shift = Shift::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        $shift->update(array_filter([
            'scheduled_start' => $request->scheduledStart,
            'scheduled_end'   => $request->scheduledEnd,
            'label'           => $request->label,
        ], fn($v) => $v !== null));

        return response()->json(['shift' => ShiftResource::make($shift->load('site'))]);
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\Response
    {
        Shift::where('id', $id)->where('org_id', $request->org_id)->firstOrFail()->delete();
        return response()->noContent();
    }
}
