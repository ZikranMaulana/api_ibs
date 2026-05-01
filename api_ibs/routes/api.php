<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CabangController;

// --- Rute Publik (Tidak butuh Token) ---
Route::post('/register', [AuthController::class, 'register']);
use App\Http\Controllers\Api\LembagaController;
// Route Public (Tidak memerlukan token, bisa diakses langsung)
Route::post('/login', [AuthController::class, 'login']);
Route::apiResource('cabangs', CabangController::class);

// --- Rute Dilindungi (Wajib Token) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'update']);
    Route::delete('/delete-account', [AuthController::class, 'destroy']);
 
    // Cek Data User + Relasi Role-nya
    // CRUD Lembaga Routes
    Route::apiResource('lembaga', LembagaController::class);
    
    // Endpoint ini sangat penting untuk React:
    // Digunakan saat aplikasi React pertama kali load untuk mengecek siapa yang sedang login

    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('role')
        ]);
    });
});