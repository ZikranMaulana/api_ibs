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

        // 2. Mulai query dasar (cek parameter trashed)
        if ($request->has('trashed')) {
            $query = User::with(['role', 'deleter'])->where('status', 3);
        } else {
            $query = User::with('role')->where('status', '!=', 3);
        }

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
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id'
        ]);

        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $data['status'] = 1;
        $data['created_by'] = auth()->id();

        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil ditambahkan',
            'log'     => 'Berhasil melakukan penambahan data user (Status 1)',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('role')->where('id', $id)->where('status', '!=', 3)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::where('id', $id)->where('status', '!=', 3)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string',
            'username' => 'sometimes|required|string|unique:users,username,' . $id,
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id'
        ]);

        $data = $request->all();
        if ($request->filled('password')) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        
        $data['status'] = 2;
        $data['updated_by'] = auth()->id();

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diupdate',
            'log'     => 'Berhasil melakukan pengeditan data user (Status 2)',
            'data' => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::where('id', $id)->where('status', '!=', 3)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        // --- AUTHORIZATION LOGIC ---
        $currentUser = auth()->user()->load('role');
        $currentUserRole = $currentUser->role->kode;
        
        $targetUser = $user->load('role');
        $targetUserRole = $targetUser->role->kode ?? '';

        // 1. User biasa (bukan ADM001 dan bukan ADM002) DILARANG hapus siapapun
        if ($currentUserRole !== 'ADM001' && $currentUserRole !== 'ADM002') {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk menghapus data.'], 403);
        }

        // 2. Admin (ADM002) dilarang hapus Superadmin (ADM001) atau sesama Admin (ADM002)
        if ($currentUserRole === 'ADM002') {
            if ($targetUserRole === 'ADM001' || $targetUserRole === 'ADM002') {
                return response()->json(['status' => 'error', 'message' => 'Admin tidak boleh menghapus sesama Admin atau Superadmin.'], 403);
            }
        }

        // 3. Superadmin dilarang hapus dirinya sendiri
        if ($currentUser->id === $targetUser->id) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 403);
        }
        // ---------------------------

        $user->update([
            'status' => 3,
            'deleted_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dihapus',
            'log'     => 'Berhasil melakukan penghapusan data user (Status 3 - Soft Delete)',
        ], 200);
    }
}
