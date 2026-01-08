<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VapiWebhookController;
use App\Http\Controllers\EnrollmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//handles Vapi Webhooks (Exclude from CSRF) - client call results
Route::post('/webhooks/vapi', [VapiWebhookController::class, 'handle']);

// Paystack Webhook (Exclude from CSRF)
Route::post('/webhooks/paystack', [EnrollmentController::class, 'handleWebhook']);

