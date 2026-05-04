<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LembagaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('trashed')) {
            $lembaga = \App\Models\Lembaga::with('deleter')->where('status', 3)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'List Data Lembaga Terhapus (Trash)',
                'data' => $lembaga
            ]);
        }

        $lembaga = \App\Models\Lembaga::where('status', '!=', 3)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $lembaga
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lembaga' => 'required|string|max:255|unique:lembagas,nama_lembaga',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:lembagas,email',
        ]);

        $data = $request->all();
        $data['status'] = 1;
        $data['created_by'] = auth()->id();
        $lembaga = \App\Models\Lembaga::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembaga berhasil disimpan',
            'log'     => 'Berhasil melakukan penambahan data lembaga (Status 1)',
            'data' => $lembaga
        ], 201);
    }

    public function show(string $id)
    {
        $lembaga = \App\Models\Lembaga::where('id', $id)->where('status', '!=', 3)->first();

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

    public function update(Request $request, string $id)
    {
        $lembaga = \App\Models\Lembaga::where('id', $id)->where('status', '!=', 3)->first();

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

        $data = $request->all();
        $data['status'] = 2;
        $data['updated_by'] = auth()->id();
        $lembaga->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembaga berhasil diupdate',
            'log'     => 'Berhasil melakukan pengeditan data lembaga (Status 2)',
            'data' => $lembaga
        ]);
    }

    public function destroy(string $id)
    {
        $lembaga = \App\Models\Lembaga::where('id', $id)->where('status', '!=', 3)->first();
        
        if ($lembaga) {
            $lembaga->update([
                'status' => 3,
                'deleted_by' => auth()->id(),
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Data lembaga berhasil dihapus',
                'log'     => 'Berhasil melakukan penghapusan data lembaga (Status 3 - Soft Delete)',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ], 404);
    }
}
