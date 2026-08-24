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
    ],

    /*
    | Session key holding the access token of a paid purchase, set when a buyer
    | arrives from their access page. Session-scoped on purpose: the token is the
    | buyer's own key (same one that gates /resources/access/{token}), so it should
    | not sit in a URL any longer than the one redirect it takes to store it.
    */
    'unlock_session_key' => 'guide_access_token',
];
