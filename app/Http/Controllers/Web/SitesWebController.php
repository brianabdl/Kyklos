<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SitesWebController extends Controller
{
    public function index()
    {
        $sites = Site::where('org_id', Auth::user()->org_id)->orderBy('name')->get();
        return view('dashboard.sites', compact('sites'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'nullable|string|max:500',
            'lat'               => 'required|numeric|between:-90,90',
            'lng'               => 'required|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:10|max:50000',
        ]);

        Site::create(array_merge(
            $request->only('name', 'address', 'lat', 'lng', 'geofence_radius_m'),
            ['org_id' => Auth::user()->org_id]
        ));

        return redirect()->route('dashboard.sites')->with('success', 'Site created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'address'           => 'nullable|string|max:500',
            'lat'               => 'required|numeric|between:-90,90',
            'lng'               => 'required|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:10|max:50000',
        ]);

        $site = Site::where('id', $id)->where('org_id', Auth::user()->org_id)->firstOrFail();
        $site->update($request->only('name', 'address', 'lat', 'lng', 'geofence_radius_m'));

        return redirect()->route('dashboard.sites')->with('success', 'Site updated.');
    }

    public function destroy(string $id)
    {
        Site::where('id', $id)->where('org_id', Auth::user()->org_id)->firstOrFail()->delete();
        return redirect()->route('dashboard.sites')->with('success', 'Site deleted.');
    }
}
