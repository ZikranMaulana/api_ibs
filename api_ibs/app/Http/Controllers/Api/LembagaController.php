<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LembagaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lembaga = \App\Models\Lembaga::all();
        
        return response()->json([
            'status' => 'success',
            'data' => $lembaga
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_lembaga' => 'required|string|max:255|unique:lembagas,nama_lembaga',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:lembagas,email',
        ]);

        $lembaga = \App\Models\Lembaga::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembaga berhasil disimpan',
            'data' => $lembaga
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lembaga = \App\Models\Lembaga::find($id);

        if (!$lembaga) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $lembaga
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $lembaga = \App\Models\Lembaga::find($id);

        if (!$lembaga) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'nama_lembaga' => 'sometimes|required|string|max:255|unique:lembagas,nama_lembaga,' . $id,
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:lembagas,email,' . $id,
        ]);

        $lembaga->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembaga berhasil diupdate',
            'data' => $lembaga
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $lembaga = \App\Models\Lembaga::find($id);
        
        if ($lembaga) {
            $lembaga->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Data lembaga berhasil dihapus'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ], 404);
    }
}
