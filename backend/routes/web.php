<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => 'PhishRakshak Backend API is live',
    ]);
});

Route::get('/login', function () {
    return response()->json([
        'status' => false,
        'message' => 'Unauthenticated.',
    ], 401);
})->name('login');