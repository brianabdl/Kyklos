<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeLoginController extends Controller
{
    public function show()
    {
        if (Auth::check() && Auth::user()->role === 'employee') {
            return redirect()->route('employee.clock');
        }

        return view('employee.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        if (Auth::user()->role !== 'employee') {
            Auth::logout();
            return back()->withErrors(['email' => 'This portal is for employees. Managers can sign in at the manager dashboard.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('employee.clock');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
