<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\PunchException;
use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\PunchService;
use Illuminate\Http\Request;

class EmployeePunchController extends Controller
{
    public function __construct(private PunchService $punch) {}

    public function index(Request $request)
    {
        $user          = $request->user();
        $activeSession = $this->punch->activeSession($user);
        $sites         = Site::where('org_id', $user->org_id)->orderBy('name')->get();

        return view('employee.clock', compact('activeSession', 'sites'));
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'site_id' => 'required|uuid',
            'method'  => 'required|in:gps,qr,manual,selfie',
            'lat'     => 'nullable|numeric',
            'lng'     => 'nullable|numeric',
        ]);

        try {
            $result = $this->punch->clockIn($request->user(), [
                'siteId' => $request->site_id,
                'method' => $request->method,
                'lat'    => $request->lat,
                'lng'    => $request->lng,
            ]);

            $siteName = $result['session']->site->name;
            return redirect()->route('employee.clock')->with('success', "Clocked in at {$siteName}.");
        } catch (PunchException $e) {
            return redirect()->route('employee.clock')->withErrors(['punch' => $e->getMessage()]);
        }
    }

    public function breakStart(Request $request)
    {
        try {
            $this->punch->breakStart($request->user());
            return redirect()->route('employee.clock')->with('success', 'Break started.');
        } catch (PunchException $e) {
            return redirect()->route('employee.clock')->withErrors(['punch' => $e->getMessage()]);
        }
    }

    public function breakEnd(Request $request)
    {
        try {
            $this->punch->breakEnd($request->user());
            return redirect()->route('employee.clock')->with('success', 'Break ended. Back to work!');
        } catch (PunchException $e) {
            return redirect()->route('employee.clock')->withErrors(['punch' => $e->getMessage()]);
        }
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'method' => 'required|in:gps,qr,manual,selfie',
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
        ]);

        try {
            $session = $this->punch->clockOut($request->user(), [
                'method' => $request->method,
                'lat'    => $request->lat,
                'lng'    => $request->lng,
            ]);

            $h = intdiv($session->work_seconds, 3600);
            $m = intdiv($session->work_seconds % 3600, 60);
            return redirect()->route('employee.clock')->with('success', "Clocked out. You worked {$h}h {$m}m today.");
        } catch (PunchException $e) {
            return redirect()->route('employee.clock')->withErrors(['punch' => $e->getMessage()]);
        }
    }
}
