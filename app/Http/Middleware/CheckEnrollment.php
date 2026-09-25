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

        // Same resolver as the dashboard, so the row that grants access is the row
        // progress is read from. They used to disagree - see Enrollment::currentFor.
        $enrollment = Enrollment::currentFor(auth()->user()->email);

        if (!$enrollment) {
            return redirect('/checkout')->with('error', 'Please complete your enrollment to access the terminal.');
        }

        // Installment balance overdue past the grace period — access is paused until it clears.
        if ($enrollment->access_suspended) {
            return redirect('/checkout')->with('error', 'Your installment balance is overdue. Please clear it to restore access to the terminal.');
        }

        return $next($request);
    }
}