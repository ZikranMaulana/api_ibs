<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required',
            'cabang_id' => 'nullable|exists:cabangs,id' // Tambahkan ini
        ]);

        $role = Role::where('id', $request->role_id)->orWhere('kode', $request->role_id)->first();

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan.'], 422);
        }

        // VALIDASI CABANG: Tolak jika mencoba buat akun non-superadmin tapi cabang kosong
        if ($role->kode !== 'ADM001' && empty($request->cabang_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak boleh kosong. Pendaftaran role ini mewajibkan pemilihan Cabang.'
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'cabang_id' => $role->kode === 'ADM001' ? null : $request->cabang_id, // Superadmin = null
        ]);

        $user->load('role', 'cabang');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    // --- 2. LOGIN ---
    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string', // Bisa email atau username
            'password' => 'required|string',
        ]);

        // Cari berdasarkan Email atau Username
        $user = User::with('role') // Langsung ambil data rolenya
                    ->where('email', $request->login_id)
                    ->orWhere('username', $request->login_id)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial tidak valid. Cek Email/Username dan Password Anda.'
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

    public function update(Request $request)
    {
        // 1. Ambil HANYA user yang sedang login saat ini (Berdasarkan Token Bearer)
        $user = $request->user(); 

        // 2. Validasi input (termasuk PIN)
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'pin' => 'sometimes|required|string|min:6', // Validasi PIN minimal 6 karakter
        ]);

        // 3. Ambil data dasar yang ingin diupdate
        $dataToUpdate = $request->only(['name', 'username', 'email']);

        // 4. Jika user mengirimkan data PIN baru, kita HASH terlebih dahulu, lalu masukkan ke array update
        if ($request->has('pin')) {
            $dataToUpdate['pin'] = Hash::make($request->pin);
        }

        // 5. Lakukan update HANYA pada user ini saja
        $user->update($dataToUpdate);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->load('role')
        ], 200);
    }

    // --- 5. HAPUS AKUN (DELETE) ---
    public function destroy(Request $request)
    {
        $user = $request->user(); // Ambil data user yang sedang login saat ini

        // (Opsional tapi disarankan) Hapus semua token yang dimiliki user ini agar benar-benar ter-logout dari semua perangkat
        $user->tokens()->delete();

        // Hapus data user dari database
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dihapus permanen'
        ], 200);
    }

    // --- 4. LOGOUT ---
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ], 200);
    }
}