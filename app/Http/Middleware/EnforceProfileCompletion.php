<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceProfileCompletion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // If the user has a member profile and it is NOT complete
            if ($user->member && !$user->member->is_complete) {
                // Redirect to completion page unless already there or logging out
                if (!$request->routeIs('members.complete', 'logout')) {
                    return redirect()->route('members.complete');
                }
            }
        }

        return $next($request);
    }
}
