<?php

namespace App\Http\Controllers;

use App\Exceptions\PunchException;
use App\Http\Resources\PunchSessionResource;
use App\Models\PunchSession;
use App\Services\PunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PunchController extends Controller
{
    public function __construct(private PunchService $punch) {}

    public function clockIn(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'siteId'   => 'required|uuid',
                'lat'      => 'required_if:method,gps|nullable|numeric',
                'lng'      => 'required_if:method,gps|nullable|numeric',
                'method'   => 'required|in:qr,gps,selfie,manual',
                'photoUrl' => 'nullable|url',
            ]);

            $result = $this->punch->clockIn($request->user(), [
                'siteId'   => $request->siteId,
                'method'   => $request->method,
                'lat'      => $request->lat,
                'lng'      => $request->lng,
                'photoUrl' => $request->photoUrl,
            ]);

            return response()->json([
                'session'       => PunchSessionResource::make($result['session']),
                'geofenceCheck' => $result['geofenceCheck'],
            ], 201);
        } catch (PunchException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'statusCode' => $e->statusCode]], $e->statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['code' => 'VALIDATE_EXCEPTION', 'message' => $e->errors(), 'statusCode' => 422]], 422);
        }
    }

    public function active(Request $request): JsonResponse
    {
        $session = $this->punch->activeSession($request->user());

        if (!$session) {
            return response()->json(['error' => ['code' => 'NO_ACTIVE_SESSION', 'message' => 'No active session.', 'statusCode' => 404]], 404);
        }

        return response()->json(['session' => PunchSessionResource::make($session)]);
    }

    public function breakStart(Request $request): JsonResponse
    {
        try {
            $session = $this->punch->breakStart($request->user());
            return response()->json(['message' => 'Break started.', 'session' => PunchSessionResource::make($session)]);
        } catch (PunchException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'statusCode' => $e->statusCode]], $e->statusCode);
        }
    }

    public function breakEnd(Request $request): JsonResponse
    {
        try {
            $session = $this->punch->breakEnd($request->user());
            return response()->json(['message' => 'Break ended.', 'session' => PunchSessionResource::make($session)]);
        } catch (PunchException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'statusCode' => $e->statusCode]], $e->statusCode);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month'    => 'sometimes|date_format:Y-m',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = PunchSession::where('user_id', $request->user()->id)
            ->where('state', 'clocked_out')
            ->with('site', 'shift', 'breaks')
            ->orderByDesc('clocked_in_at');

        if ($request->filled('month')) {
            [$year, $mon] = explode('-', $request->month);
            $start = \Carbon\Carbon::create($year, $mon, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $query->whereBetween('clocked_in_at', [$start, $end]);
        }

        $sessions = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'sessions' => PunchSessionResource::collection($sessions),
            'meta'     => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $session = PunchSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('site', 'shift', 'breaks', 'events')
            ->firstOrFail();

        return response()->json(['session' => PunchSessionResource::make($session)]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'method' => 'required|in:qr,gps,selfie,manual',
                'lat'    => 'nullable|numeric',
                'lng'    => 'nullable|numeric',
            ]);

            $session = $this->punch->clockOut($request->user(), [
                'method' => $request->method,
                'lat'    => $request->lat,
                'lng'    => $request->lng,
            ]);

            return response()->json(['session' => PunchSessionResource::make($session)]);
        } catch (PunchException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'statusCode' => $e->statusCode]], $e->statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => ['code' => 'VALIDATE_EXCEPTION', 'message' => $e->errors(), 'statusCode' => 422]], 422);
        }
    }
}
