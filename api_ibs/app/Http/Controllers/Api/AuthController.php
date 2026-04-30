<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string', 
            'password' => 'required|string',
        ]);

        $login_id = $request->login_id;

        $user = User::where(function ($query) use ($login_id) {
            // Kriteria 1: Santri login WAJIB pakai NIS
            $query->where('role', 'santri')
                  ->where('nis', $login_id);
        })->orWhere(function ($query) use ($login_id) {
            // Kriteria 2: Admin & Merchant login WAJIB pakai Username atau Email
            $query->whereIn('role', ['admin', 'merchant'])
                  ->where(function ($q) use ($login_id) {
                      $q->where('username', $login_id)
                        ->orWhere('email', $login_id);
                  });
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial tidak valid. Santri gunakan NIS, Admin/Kantin gunakan Username atau Email.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ], 200);
    }
}
