<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check() && Auth::user()->role === 'manager') {
            return redirect()->route('dashboard.overview');
        }

        return view('dashboard.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->where('is_active', true)->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->role === 'employee') {
            Auth::login($user);
            return redirect()->route('employee.clock');
        }

        if ($user->pin_hash) {
            $request->session()->put('pending_manager_id', $user->id);
            $request->session()->put('pending_manager_exp', now()->addMinutes(5)->timestamp);
            return redirect()->route('dashboard.pin');
        }

        Auth::login($user);
        return redirect()->route('dashboard.overview');
    }

    public function showPin(Request $request)
    {
        if (!$request->session()->has('pending_manager_id')) {
            return redirect()->route('dashboard.login');
        }

        return view('dashboard.pin');
    }

    public function verifyPin(Request $request)
    {
        $request->validate(['pin' => 'required|digits:4']);

        $userId = $request->session()->get('pending_manager_id');
        $exp    = $request->session()->get('pending_manager_exp');

        if (!$userId || !$exp || now()->timestamp > $exp) {
            $request->session()->forget(['pending_manager_id', 'pending_manager_exp']);
            return redirect()->route('dashboard.login')
                ->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        $pinKey = 'web_pin:' . $userId;
        if (RateLimiter::tooManyAttempts($pinKey, 5)) {
            return back()->withErrors(['pin' => 'Too many incorrect attempts. Please wait before trying again.']);
        }

        $user = User::findOrFail($userId);

        if (!Hash::check($request->pin, $user->pin_hash)) {
            RateLimiter::hit($pinKey, 300);
            $remaining = 5 - RateLimiter::attempts($pinKey);
            return back()->withErrors(['pin' => "Incorrect PIN. {$remaining} attempt(s) remaining."]);
        }

        RateLimiter::clear($pinKey);
        $request->session()->forget(['pending_manager_id', 'pending_manager_exp']);
        Auth::login($user);

        return redirect()->route('dashboard.overview');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard.login');
    }
}
