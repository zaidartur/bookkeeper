<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ping', function(Request $request) {
    $validated = $request->validate([
        'ip_address' => 'required|ip',
    ]);

    $ipAddress = $validated['ip_address'];

    try {
        $serviceUrl = rtrim(env('PING_SERVICE_URL', 'http://127.0.0.1:5005'), '/') . '/ping';
        $response = Http::timeout(5)->post($serviceUrl, [
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