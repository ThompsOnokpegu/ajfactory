<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gated written guides
    |--------------------------------------------------------------------------
    | Paths protected by `App\Http\Middleware\GuideAccess`. These pages are a PAID
    | resource that Accelerator students read for free.
    |
    | They were served fully public until 24 Aug 2026 - a miscommunication, not a
    | decision. Anything added here is gated the moment it's listed, so a new guide
    | is private by default rather than needing an admin to remember.
    |
    | ONE PRODUCT, BOTH ROUTES. A student only ever follows one of these: Google
    | Cloud, or Hostinger when Google won't verify their account. Charging twice for
    | a fallback route would be a trap, so a paid purchase of either unlocks both.
    |
    | The sellable `Resource` row is matched by its `url` being one of these paths.
    | Until the owner creates one in Admin -> Resources, the guides simply stay
    | student-only and the locked page points at the Accelerator instead of a
    | Buy button - it never invents a price.
    */
    'gated_paths' => [
        '/guides/n8n-on-google-cloud',
        '/guides/n8n-on-hostinger',
        '/guides/capstone-part1-quote-engine',
    ],

    /*
    | Of the gated paths above, the ones a PURCHASE buys. Gating and selling are
    | not the same thing: everything above is hidden from the public, but only
    | these are for sale, and a buyer gets exactly these.
    |
    | Split out on 9 Sep 2026. Before that, any gated path unlocked every gated
    | path - fine while the list was just the two self-hosting routes, and wrong
    | the moment the capstone brief was added to keep it off the open web. A
    | 15,000 naira guide sale was handing over Accelerator course content.
    |
    | Rule of thumb: a path belongs here only if someone could reasonably buy it
    | on its own. Course material never does - it is gated, not sold.
    */
    'purchasable_paths' => [
        '/guides/n8n-on-google-cloud',
        '/guides/n8n-on-hostinger',
    ],

    /*
    | Session key holding the access token of a paid purchase, set when a buyer
    | arrives from their access page. Session-scoped on purpose: the token is the
    | buyer's own key (same one that gates /resources/access/{token}), so it should
    | not sit in a URL any longer than the one redirect it takes to store it.
    */
    'unlock_session_key' => 'guide_access_token',
];
