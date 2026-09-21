<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'guide' => \App\Http\Middleware\GuideAccess::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/webhooks/paystack',        // Exclude paystack webhook route URI'
            'api/webhooks/vapi', // Exclude Vapi webhook route URI
            'api/webhooks/flutterwave', // Exclude Flutterwave webhook route URI
        ]);

        // Meta's pixel writes _fbp/_fbc itself, unencrypted. EncryptCookies nulls any
        // cookie it cannot decrypt, so without this exception the checkout would
        // store null for both and every server-side Purchase would match poorly.
        // MetaPixelTest guards it.
        $middleware->encryptCookies(except: ['_fbp', '_fbc']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
