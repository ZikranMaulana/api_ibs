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
    public function index()
    {
        $roles = Role::all();
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

        $role = Role::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil ditambahkan',
            'data' => $role
        ], 201);
    }

    /**
     * Tampilkan Satu Role (Berdasarkan ID atau Kode)
     */
    public function show($id_or_kode)
    {
        // Cari berdasarkan ID atau KODE
        $role = Role::where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();

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
        $role = Role::where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        $request->validate([
            'kode' => 'sometimes|required|string|max:10|unique:roles,kode,' . $role->id,
            'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string'
        ]);

        $role->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil diupdate',
            'data' => $role
        ], 200);
    }

    /**
     * Hapus Role (Berdasarkan ID atau Kode)
     */
    public function destroy($id_or_kode)
    {
        $role = Role::where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role berhasil dihapus'
        ], 200);
    }
}