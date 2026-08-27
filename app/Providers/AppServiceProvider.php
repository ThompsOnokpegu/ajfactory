<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forcePublicUrlInConsole();
    }

    /**
     * Make absolute URLs built from the CONSOLE point at the real site.
     *
     * URL::signedRoute() takes its host from the incoming request, but a scheduled
     * command has no request, so Laravel falls back to config('app.url'). The server
     * still carried Laravel's default, so installments:process emailed pay links at
     * http://localhost - and because the signature is an HMAC over the whole absolute
     * URL, those links 403 and cannot be repaired by rewriting the host afterwards.
     *
     * Rather than depend on the server .env ever being edited, fall back to the known
     * public origin - the same trick the Telegram links use in config/accelerator.php.
     * A properly configured APP_URL always wins; this only rescues a local or missing
     * one, and stays out of the way in tests.
     */
    private function forcePublicUrlInConsole(): void
    {
        if (! $this->app->runningInConsole() || $this->app->environment('testing')) {
            return;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        // A real APP_URL is configured - respect it.
        if (! in_array($host, ['localhost', '127.0.0.1', '::1', null, false], true)) {
            return;
        }

        $public = trim((string) config('app.public_url'));

        if ($public === '') {
            return;
        }

        URL::forceRootUrl($public);

        // forceRootUrl sets the host but NOT the scheme, and an http -> https redirect
        // breaks a signature just as thoroughly as the wrong host does.
        if (str_starts_with($public, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
