<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('employee.login');
        }

        if (auth()->user()->role !== 'employee') {
            auth()->logout();
            return redirect()->route('employee.login')
                ->withErrors(['email' => 'This portal is for employees only.']);
        }

        return $next($request);
    }
}
