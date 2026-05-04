<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil user yang sedang login
        $currentUser = $request->user()->load('role');
        $roleKode = $currentUser->role->kode;
        $cabangId = $currentUser->cabang_id; // Ambil ID Cabang user yang sedang login

        // 2. Mulai query dasar (Load relasi role dan cabang)
        if ($request->has('trashed')) {
            $query = User::with(['role', 'cabang', 'deleter'])->where('status', 3);
        } else {
            $query = User::with(['role', 'cabang'])->where('status', '!=', 3);
        }

        // 3. Terapkan logika pembatasan data berdasarkan kode role dan Cabang
        if ($roleKode === 'ADM001') {
            
            // SUPERADMIN: Bebas melihat semua cabang.
            if ($request->has('role_id')) { $query->where('role_id', $request->role_id); }
            if ($request->has('cabang_id')) { $query->where('cabang_id', $request->cabang_id); }

        } elseif ($roleKode === 'ADM002') {
            
            // ADMIN CABANG: Boleh melihat semua role, TAPI HANYA di cabangnya sendiri.
            $query->where('cabang_id', $cabangId);
            if ($request->has('role_id')) { $query->where('role_id', $request->role_id); }

        } elseif ($roleKode === 'KNT001') {
            
            // ADMIN KANTIN: HANYA melihat role 'kantin' (KNT002) di cabangnya sendiri.
            $query->where('cabang_id', $cabangId)->whereHas('role', function ($q) {
                $q->where('kode', 'KNT002');
            });

        } else {
            
            // LAINNYA (Misal Wali Santri): Hanya melihat datanya sendiri.
            $query->where('id', $currentUser->id);
            
        }

        $users = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data akun',
            'data' => $users
        ], 200);
    }

    public function store(Request $request)
    {
        $currentUserRole = auth()->user()->role->kode;

        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'cabang_id' => 'nullable|exists:cabangs,id' 
        ]);

        $data = $request->all();

        // VALIDASI CABANG
        $roleTarget = \App\Models\Role::find($request->role_id);
        if ($roleTarget && $roleTarget->kode !== 'ADM001' && empty($request->cabang_id)) {
            // Jika Admin (ADM002) yang membuat akun, otomatis gunakan cabang miliknya.
            if ($currentUserRole !== 'ADM001') {
                $data['cabang_id'] = auth()->user()->cabang_id;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Cabang WAJIB diisi untuk user selain Superadmin.'], 422);
            }
        }

        $data['password'] = bcrypt($data['password']);
        $data['status'] = 1;
        $data['created_by'] = auth()->id();

        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil ditambahkan',
            'data' => $user->load('cabang', 'role')
        ], 201);
    }

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

    public function update(Request $request, string $id)
    {
        // 1. Cari target user yang ingin diupdate
        $targetUser = User::with('role')->where('id', $id)->where('status', '!=', 3)->first();

        if (!$targetUser) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        // --- AUTHORIZATION LOGIC (HAK AKSES) ---
        $currentUser = $request->user()->load('role');
        $currentUserRole = $currentUser->role->kode;
        $currentCabangId = $currentUser->cabang_id;
        
        $targetRoleKode = $targetUser->role->kode ?? '';

        if ($currentUserRole === 'ADM001') {
            // SUPERADMIN: Bebas mengupdate data SIAPAPUN. (Lolos pengecekan)
        } 
        elseif ($currentUserRole === 'ADM002') {
            // ADMIN CABANG: Hanya boleh update user di cabangnya sendiri & Dilarang update Superadmin
            if ($targetUser->cabang_id !== $currentCabangId || $targetRoleKode === 'ADM001') {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki wewenang untuk mengupdate akun ini.'], 403);
            }
        } 
        elseif ($currentUserRole === 'KNT001') {
            // ADMIN KANTIN: Hanya boleh update akun dengan role 'Kantin' di cabangnya sendiri
            if ($targetUser->cabang_id !== $currentCabangId || $targetRoleKode !== 'KNT002') {
                return response()->json(['status' => 'error', 'message' => 'Anda hanya diizinkan mengupdate data Kantin di cabang Anda.'], 403);
            }
        } 
        else {
            // ROLE LAIN: Dilarang menggunakan endpoint ini untuk mengedit orang lain. 
            // (Untuk edit profil sendiri, mereka harus menggunakan AuthController@update)
            if ($currentUser->id !== $targetUser->id) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }
        }
        // --- END AUTHORIZATION LOGIC ---

        // 2. Validasi Inputan
        $request->validate([
            'name' => 'sometimes|required|string',
            'username' => 'sometimes|required|string|unique:users,username,' . $id,
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'sometimes|required|exists:roles,id',
            'cabang_id' => 'nullable|exists:cabangs,id'
        ]);

        $data = $request->all();

        // 3. Keamanan Tambahan: Mencegah Admin biasa "menyelundupkan" akun menjadi Superadmin
        if (isset($data['role_id']) && $currentUserRole !== 'ADM001') {
            $newRole = \App\Models\Role::find($data['role_id']);
            if ($newRole && $newRole->kode === 'ADM001') {
                return response()->json(['status' => 'error', 'message' => 'Hanya Superadmin yang dapat memberikan hak akses Superadmin.'], 403);
            }
        }

        // 4. Proses Update Password jika ada
        if ($request->filled('password')) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        
        $data['status'] = 2; // Status 2 = Data pernah diedit
        $data['updated_by'] = auth()->id();

        // 5. Simpan Perubahan
        $targetUser->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diupdate',
            'log'     => 'Berhasil melakukan pengeditan data user (Status 2)',
            'data' => $targetUser->fresh()->load('cabang', 'role')
        ], 200);
    }

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
