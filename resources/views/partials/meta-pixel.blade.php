{{--
    Meta Pixel - the ONLY place the pixel is rendered. Include right before </head> on
    every public page (standalone pages do it themselves; the TAAB and student layouts
    do it for the pages they wrap). Renders nothing when META_PIXEL_ID is unset.

    Usage:
      @include('partials.meta-pixel')                                   PageView only
      @include('partials.meta-pixel', ['metaEvent' => 'ViewContent', 'metaParams' => [...]])
      @include('partials.meta-pixel', ['metaEvent' => 'Purchase', 'metaEventId' => $ref, 'metaOnce' => true, ...])

    Params (prefixed so they never collide with a page's own $status/$title/$event):
      metaEvent    one standard event to fire after PageView (ViewContent, InitiateCheckout, Purchase, Lead)
      metaEventId  dedup key - for Purchase it MUST be the payment reference, the same
                   value the Conversions API sends as event_id, or Meta counts the sale twice
      metaParams   event parameters (value, currency, content_name, content_ids, content_type)
      metaMatch    advanced matching: em/ph/fn/ln as PLAIN values - fbevents.js hashes them client-side
      metaOnce     guard the event with localStorage so a refresh or a bookmarked revisit
                   can't fire it again (dedup with the server only lasts 48h)

    Never include this in the dashboard, admin, auth, or paid-guide chrome: those are
    logged-in students, not prospects.
--}}
@php
    $metaPixelId = config('services.meta.pixel_id');
    $metaEvent   = $metaEvent   ?? null;
    $metaEventId = $metaEventId ?? null;
    $metaParams  = $metaParams  ?? [];
    $metaMatch   = array_filter($metaMatch ?? []);
    $metaOnce    = $metaOnce    ?? false;
@endphp
@if($metaPixelId)
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', @json((string) $metaPixelId)@if($metaMatch), @json($metaMatch)@endif);
fbq('track', 'PageView');
@if($metaEvent)
(function () {
  @if($metaOnce && $metaEventId)
  var k = 'meta_' + @json($metaEvent) + '_' + @json($metaEventId);
  try { if (localStorage.getItem(k)) return; localStorage.setItem(k, '1'); } catch (e) {}
  @endif
  fbq('track', @json($metaEvent), @json((object) $metaParams)@if($metaEventId), { eventID: @json($metaEventId) }@endif);
})();
@endif
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" alt=""/></noscript>
<!-- End Meta Pixel -->
@endif
