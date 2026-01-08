<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use Symfony\Component\HttpFoundation\Response;

class CheckEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Admins always have access
        if (auth()->user()->is_admin) {
            return $next($request);
        }

        $enrollment = Enrollment::where('email', auth()->user()->email)
            ->where('status', 'paid')
            ->first();

        if (!$enrollment) {
            return redirect('/checkout')->with('error', 'Please complete your enrollment to access the terminal.');
        }

        return $next($request);
    }
}