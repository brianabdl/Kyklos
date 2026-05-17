<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrgIsolationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge(['org_id' => $request->user()->org_id]);
        return $next($request);
    }
}
