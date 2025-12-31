<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        if (!$user->member) {
            abort(403, 'Member account required to access this page.');
        }

        // Enforce profile completion
        if (!$user->member->is_complete && !$request->routeIs('members.complete', 'logout')) {
            return redirect()->route('members.complete');
        }

        return $next($request);
    }
}
