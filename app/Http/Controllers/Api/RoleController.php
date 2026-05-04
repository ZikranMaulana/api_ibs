<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
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

    public function show($id_or_kode)
    {
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
     * Aktif / Non-aktifkan Role
     */
    public function toggleStatus($id_or_kode)
    {
        $role = Role::where(function($query) use ($id_or_kode) {
            $query->where('id', $id_or_kode)->orWhere('kode', $id_or_kode);
        })->where('status', '!=', 3)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak ditemukan'], 404);
        }

        if ($role->status == 1 || $role->status == 2) {
            $role->update([
                'status' => 0,
                'updated_by' => auth()->id()
            ]);
            $msg = 'Role berhasil dinonaktifkan (Status 0)';
        } else {
            $role->update([
                'status' => 1,
                'updated_by' => auth()->id()
            ]);
            $msg = 'Role berhasil diaktifkan kembali (Status 1)';
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg,
            'data' => $role
        ], 200);
    }

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