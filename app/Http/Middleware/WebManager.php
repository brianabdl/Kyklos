<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('dashboard.login');
        }

        if (auth()->user()->role !== 'manager') {
            auth()->logout();
            return redirect()->route('dashboard.login')->withErrors(['email' => 'Access restricted to managers.']);
        }

        return $next($request);
    }
}
