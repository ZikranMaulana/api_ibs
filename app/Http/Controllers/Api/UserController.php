<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Menampilkan semua data pengguna
     */
    public function index(Request $request)
    {
        // 1. Ambil user yang sedang login beserta data role-nya
        $currentUser = $request->user()->load('role');
        $roleKode = $currentUser->role->kode;

        // 2. Mulai query dasar
        $query = User::with('role');

        // 3. Terapkan logika pembatasan data berdasarkan kode role
        if ($roleKode === 'ADM001' || $roleKode === 'ADM002') {
            
            // Superadmin dan Admin BISA melihat semua data.
            // Jika frontend mengirim parameter pencarian role_id, terapkan filternya.
            if ($request->has('role_id')) {
                $query->where('role_id', $request->role_id);
            }

        } elseif ($roleKode === 'KNT001') {
            
            // Admin Kantin HANYA boleh melihat akun dengan role 'kantin' (KNT002).
            // Kita gunakan whereHas untuk memfilter berdasarkan kolom di tabel relasi (roles).
            $query->whereHas('role', function ($q) {
                $q->where('kode', 'KNT002');
            });

        } else {
            
            // Untuk role lain (seperti Wali Santri 'WLI001'), kita batasi agar 
            // mereka HANYA bisa melihat datanya sendiri sebagai tindakan pengamanan.
            $query->where('id', $currentUser->id);
            
        }

        $users = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data akun',
            'data' => $users
        ], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
