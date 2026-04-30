<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Route Public (Tidak memerlukan token, bisa diakses langsung)
Route::post('/login', [AuthController::class, 'login']);

// Route Protected (Wajib melampirkan Token Bearer dari proses login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Endpoint ini sangat penting untuk React:
    // Digunakan saat aplikasi React pertama kali load untuk mengecek siapa yang sedang login
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    });
});