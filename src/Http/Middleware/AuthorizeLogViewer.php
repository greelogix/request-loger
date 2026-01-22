<?php

namespace GreeLogix\RequestLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeLogViewer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedEmails = config('gl-request-logger.allowed_emails', []);

        // If no allowed emails are configured, allow all authenticated users
        if (empty($allowedEmails)) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            abort(403, 'Unauthorized access to log viewer.');
        }

        // Get the authenticated user's email
        $userEmail = Auth::user()->email ?? null;

        if (!$userEmail) {
            abort(403, 'User email not found. Access denied.');
        }

        // Check if user's email is in the allowed list (case-insensitive)
        $allowedEmailsLower = array_map('strtolower', $allowedEmails);
        if (!in_array(strtolower($userEmail), $allowedEmailsLower)) {
            abort(403, 'You do not have permission to access the log viewer.');
        }

        return $next($request);
    }
}
