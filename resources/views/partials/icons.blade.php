{{--
    Brand icon set.

    Every public page on this site carries its OWN <head> - partials/head.blade.php
    is only pulled in by the authenticated Flux layouts - so none of them declared an
    icon and browsers fell back to the implicit /favicon.ico request, which served
    Laravel's default logo. Include this in any <head> you add.

    The ?v= is deliberate: a favicon is cached hard and for a long time, so without it
    returning visitors keep the old icon indefinitely. Bump it if the mark changes.
--}}
<link rel="icon" href="/favicon.ico?v=2" sizes="any">
<link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
