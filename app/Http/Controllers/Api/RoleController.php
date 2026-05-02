<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Tampilkan semua data Role
     */
    public function index(Request $request)
    {
        if ($request->has('trashed')) {
            $roles = Role::with('deleter')->where('status', 3)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'List Data Role Terhapus (Trash)',
                'data' => $roles
            ], 200);
        }

        $roles = Role::where('status', '!=', 3)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'List Data Role',
            'data' => $roles
        ], 200);
    }

    /**
     * Tambah Role Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:10|unique:roles',
            'name' => 'required|string|unique:roles',
            'description' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['status'] = 1;
        $data['created_by'] = auth()->id();
        $role = Role::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil ditambahkan',
            'log'     => 'Berhasil melakukan penambahan data role (Status 1)',
            'data' => $role
        ], 201);
    }

    /**
     * Tampilkan Satu Role (Berdasarkan ID atau Kode)
     */
    public function show($id_or_kode)
    {
        // Cari berdasarkan ID atau KODE yang belum dihapus
        $role = Role::where(function($query) use ($id_or_kode) {
            $query->where('id', $id_or_kode)->orWhere('kode', $id_or_kode);
        })->where('status', '!=', 3)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail Role',
            'data' => $role
        ], 200);
    }

    /**
     * Update Role (Berdasarkan ID atau Kode)
     */
    public function update(Request $request, $id_or_kode)
    {
        $role = Role::where(function($query) use ($id_or_kode) {
            $query->where('id', $id_or_kode)->orWhere('kode', $id_or_kode);
        })->where('status', '!=', 3)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        $request->validate([
            'kode' => 'sometimes|required|string|max:10|unique:roles,kode,' . $role->id,
            'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['status'] = 2;
        $data['updated_by'] = auth()->id();
        $role->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diupdate',
            'log'     => 'Berhasil melakukan pengeditan data role (Status 2)',
            'data' => $role
        ], 200);
    }

    /**
     * Hapus Role (Berdasarkan ID atau Kode)
     */
    public function destroy($id_or_kode)
    {
        $role = Role::where(function($query) use ($id_or_kode) {
            $query->where('id', $id_or_kode)->orWhere('kode', $id_or_kode);
        })->where('status', '!=', 3)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        $role->update([
            'status' => 3,
            'deleted_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dihapus',
            'log'     => 'Berhasil melakukan penghapusan data role (Status 3 - Soft Delete)',
        ], 200);
    }
}