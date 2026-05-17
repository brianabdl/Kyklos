<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sites = Site::where('org_id', $request->org_id)->get();
        return response()->json(['sites' => $sites]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $site = Site::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        return response()->json(['site' => $site]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'nullable|string',
            'lat'               => 'required|numeric',
            'lng'               => 'required|numeric',
            'geofence_radius_m' => 'nullable|integer|min:10',
        ]);

        $site = Site::create(array_merge(
            $request->only('name', 'address', 'lat', 'lng', 'geofence_radius_m'),
            ['org_id' => $request->org_id]
        ));

        return response()->json(['site' => $site], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name'              => 'sometimes|string|max:255',
            'address'           => 'nullable|string',
            'lat'               => 'sometimes|numeric',
            'lng'               => 'sometimes|numeric',
            'geofence_radius_m' => 'nullable|integer|min:10',
        ]);

        $site = Site::where('id', $id)->where('org_id', $request->org_id)->firstOrFail();
        $site->update($request->only('name', 'address', 'lat', 'lng', 'geofence_radius_m'));

        return response()->json(['site' => $site]);
    }
}
