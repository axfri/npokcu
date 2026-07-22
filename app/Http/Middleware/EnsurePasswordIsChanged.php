<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->must_change_password
            && ! $request->routeIs('account.password.*', 'logout', 'verification.*')
        ) {
            return redirect()->route('account.password.edit');
        }

        return $next($request);
    }
}
