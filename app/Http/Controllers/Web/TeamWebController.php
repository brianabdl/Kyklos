<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeamWebController extends Controller
{
    public function index(Request $request)
    {
        $orgId  = auth()->user()->org_id;
        $date   = $request->query('date', today()->toDateString());
        $search = $request->query('search');
        $status = $request->query('status');
        $siteId = $request->query('site');

        $query = User::where('org_id', $orgId)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->with([
                'punchSessions' => fn($q) => $q
                    ->whereDate('clocked_in_at', $date)
                    ->with('site')
                    ->latest('clocked_in_at')
                    ->limit(1),
            ]);

        if ($search) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $employees = $query->get()->map(function (User $user) {
            $session     = $user->punchSessions->first();
            $statusLabel = 'out';
            $hoursToday  = null;
            $clockedInAt = null;

            if ($session) {
                $statusLabel = match ($session->state) {
                    'active'   => 'in',
                    'on_break' => 'break',
                    default    => 'out',
                };
                $clockedInAt = $session->clocked_in_at;
                $elapsed     = now()->diffInSeconds($session->clocked_in_at);
                $hoursToday  = floor($elapsed / 3600) . 'h ' . floor(($elapsed % 3600) / 60) . 'm';
            }

            return (object) [
                'id'         => $user->id,
                'name'       => $user->full_name,
                'email'      => $user->email,
                'role'       => $user->role,
                'site'       => $session?->site?->name,
                'status'     => $statusLabel,
                'clockedInAt'=> $clockedInAt,
                'hoursToday' => $hoursToday,
            ];
        });

        if ($status) {
            $employees = $employees->filter(fn($e) => $e->status === $status)->values();
        }

        $sites = Site::where('org_id', $orgId)->orderBy('name')->get();

        return view('dashboard.team', compact('employees', 'date', 'search', 'status', 'sites', 'siteId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'role'      => 'sometimes|in:employee,manager',
        ]);

        User::create([
            'org_id'        => auth()->user()->org_id,
            'full_name'     => $request->full_name,
            'email'         => $request->email,
            'role'          => $request->input('role', 'employee'),
            'password_hash' => Hash::make($request->password),
            'is_active'     => true,
        ]);

        return redirect()->route('dashboard.team')->with('success', 'Employee added.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,'. $id,
            'role'      => 'required|in:employee,manager',
        ]);

        $user = User::where('id', $id)->where('org_id', auth()->user()->org_id)->firstOrFail();
        $user->update($request->only('full_name', 'email', 'role'));

        return redirect()->route('dashboard.team')->with('success', 'Employee updated.');
    }

    public function deactivate(Request $request, string $id)
    {
        $user = User::where('id', $id)->where('org_id', auth()->user()->org_id)->firstOrFail();
        $user->update(['is_active' => false]);

        return redirect()->route('dashboard.team')->with('success', 'Employee deactivated.');
    }

    public function resetPin(Request $request, string $id)
    {
        $request->validate(['new_pin' => 'required|digits:4']);

        $user = User::where('id', $id)->where('org_id', auth()->user()->org_id)->firstOrFail();
        $user->update(['pin_hash' => Hash::make($request->new_pin)]);

        return redirect()->route('dashboard.team')->with('success', 'PIN reset successfully.');
    }
}
