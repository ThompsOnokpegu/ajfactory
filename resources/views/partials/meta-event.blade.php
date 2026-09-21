{{--
    Fire ONE Meta standard event from a page body whose base pixel came from a layout
    (partials.meta-pixel in <head>). Same params as that partial: metaEvent, metaEventId,
    metaParams, metaOnce. Renders nothing when the pixel is unset; a no-op if fbq is
    somehow missing (the base snippet defines the stub synchronously, so on any page
    that includes it this is always a function by the time the body renders).
--}}
@if(config('services.meta.pixel_id') && ! empty($metaEvent))
<script>
(function () {
  if (typeof fbq !== 'function') return;
  @if(($metaOnce ?? false) && ! empty($metaEventId))
  var k = 'meta_' + @json($metaEvent) + '_' + @json($metaEventId);
  try { if (localStorage.getItem(k)) return; localStorage.setItem(k, '1'); } catch (e) {}
  @endif
  fbq('track', @json($metaEvent), @json((object) ($metaParams ?? []))@if(! empty($metaEventId)), { eventID: @json($metaEventId) }@endif);
})();
</script>
@endif
