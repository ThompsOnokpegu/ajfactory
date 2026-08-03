<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourcePurchase;
use Illuminate\Contracts\View\View;

class ResourceController extends Controller
{
    /** Checkout page for a paid resource (email-only, one-off). */
    public function buy(Resource $resource): View
    {
        abort_unless($resource->is_published && $resource->isPaid(), 404);

        return view('resource-buy', ['resource' => $resource]);
    }

    /**
     * Gated access page for a paid resource. Bound by the purchase's random
     * access_token (see ResourcePurchase::getRouteKeyName). The link is only
     * revealed once the webhook has marked the purchase paid.
     */
    public function access(ResourcePurchase $purchase): View
    {
        return view('resource-access', ['purchase' => $purchase->load('resource')]);
    }
}
