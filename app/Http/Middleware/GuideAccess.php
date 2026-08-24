<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use App\Models\Resource;
use App\Models\ResourcePurchase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the written guides (config/guides.php -> gated_paths).
 *
 * They're a paid resource that Accelerator students read for free. Three ways in:
 *
 *   1. An admin.
 *   2. A logged-in student with a paid, un-suspended enrolment. Same rule as
 *      CheckEnrollment uses for the terminal, deliberately - a student whose
 *      installment balance is overdue loses the guides alongside everything else,
 *      and gets them back the moment they clear it.
 *   3. Someone who bought the guide: their purchase's access_token, handed over
 *      once as `?t=` from their access page and then kept in the session.
 *
 * Everyone else gets the locked page, which sells the guide rather than 404ing -
 * a stranger landing here from a search result is a prospect, not an intruder.
 */
class GuideAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->path(), '/');

        // Not a gated guide - nothing to do. Keeps the middleware safe to apply
        // broadly, and means an un-listed guide stays public by choice.
        if (! in_array($path, config('guides.gated_paths', []), true)) {
            return $next($request);
        }

        // A buyer arriving from their access page. Store the token, then bounce to
        // the clean URL so it isn't copy-pasted around or leaked in a Referer header.
        if (is_string($token = $request->query('t')) && $token !== '') {
            if ($this->purchaseUnlocks($token)) {
                $request->session()->put(config('guides.unlock_session_key'), $token);
            }

            return redirect()->to($path);
        }

        if ($this->hasAccess($request)) {
            return $next($request);
        }

        // 200, not 403: this is a sales page, and a 403 would keep it out of search
        // results and read as an error to someone who simply hasn't bought yet.
        return response()->view('guides.locked', [
            'resource' => $this->sellableGuide(),
        ]);
    }

    private function hasAccess(Request $request): bool
    {
        $user = $request->user();

        if ($user?->is_admin) {
            return true;
        }

        if ($user) {
            $enrollment = Enrollment::where('email', $user->email)
                ->where('status', 'paid')
                ->first();

            // Mirrors CheckEnrollment: paid, and not paused for an overdue balance.
            if ($enrollment && ! $enrollment->access_suspended) {
                return true;
            }
        }

        $token = $request->session()->get(config('guides.unlock_session_key'));

        return is_string($token) && $token !== '' && $this->purchaseUnlocks($token);
    }

    /**
     * Does this access token belong to a settled purchase of a guide?
     *
     * Any gated guide unlocks every gated guide - see config/guides.php. Status is
     * re-checked on every request rather than trusted from the session, so a refund
     * or a reversal takes effect immediately.
     */
    private function purchaseUnlocks(string $token): bool
    {
        $purchase = ResourcePurchase::with('resource')
            ->where('access_token', $token)
            ->first();

        if (! $purchase || ! $purchase->isPaid()) {
            return false;
        }

        return in_array($purchase->resource?->url, config('guides.gated_paths', []), true);
    }

    /** The published, paid Resource that sells the guides, or null if none exists yet. */
    private function sellableGuide(): ?Resource
    {
        return Resource::where('is_published', true)
            ->whereIn('url', config('guides.gated_paths', []))
            ->where('price', '>', 0)
            ->orderBy('sort_order')
            ->first();
    }
}
