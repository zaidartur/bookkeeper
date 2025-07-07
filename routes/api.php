<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ping', function(Request $request) {
    $ipAddress = $request->input('ip_address');

    if (!$ipAddress) {
        return response()->json(['error' => 'IP address is required'], 400);
    }

    try {
        // Make a POST request to your Flask backend
        $response = Http::post('http://127.0.0.1:5000/ping', [
            'ip_address' => $ipAddress,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json([
                'error' => 'Failed to communicate with ping service',
                'details' => $response->json()
            ], $response->status());
        }
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An error occurred while connecting to the ping service',
            'message' => $e->getMessage()
        ], 500);
    }
});