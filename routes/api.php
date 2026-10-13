<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VapiWebhookController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\FlutterwaveWebhookController;
use App\Http\Controllers\Api\OfferController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//handles Vapi Webhooks (Exclude from CSRF) - client call results
Route::post('/webhooks/vapi', [VapiWebhookController::class, 'handleWebhook']);

// Paystack Webhook (Exclude from CSRF)
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handleWebhook']);

//Flutterwave Webhook (Exclude from CSRF)
Route::post('/webhooks/flutterwave', [FlutterwaveWebhookController::class, 'handleWebhook']);


// Live offer snapshot for Veta (bearer token, read-only)
Route::get('/offer', [OfferController::class, 'show'])->middleware('throttle:60,1');
