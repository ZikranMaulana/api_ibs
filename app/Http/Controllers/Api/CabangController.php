<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->has('trashed')) {
            $cabangs = Cabang::with('deleter')->where('status', 3)->get();
            return response()->json([
                'success' => true,
                'message' => 'List Data Cabang Terhapus (Trash)',
                'data'    => $cabangs
            ], 200);
        }

        $cabangs = Cabang::where('status', '!=', 3)->get();
        return response()->json([
            'success' => true,
            'message' => 'List Data Cabang',
            'data'    => $cabangs
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_cabang' => 'required|unique:cabangs,nama_cabang',
            'lokasi'      => 'required'
        ]);

        $cabang = Cabang::create([
            'nama_cabang' => $request->nama_cabang,
            'lokasi'      => $request->lokasi,
            'status'      => 1,
            'created_by'  => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cabang Created',
            'log'     => 'Berhasil melakukan penambahan data cabang (Status 1)',
            'data'    => $cabang
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cabang = Cabang::where('id', $id)->where('status', '!=', 3)->first();

        if ($cabang) {
            return response()->json([
                'success' => true,
                'message' => 'Detail Data Cabang',
                'data'    => $cabang
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cabang Not Found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_cabang' => 'required|unique:cabangs,nama_cabang,' . $id,
            'lokasi'      => 'required'
        ]);

        $cabang = Cabang::where('id', $id)->where('status', '!=', 3)->first();

        if ($cabang) {
            $cabang->update([
                'nama_cabang' => $request->nama_cabang,
                'lokasi'      => $request->lokasi,
                'status'      => 2,
                'updated_by'  => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang Updated',
                'log'     => 'Berhasil melakukan pengeditan data cabang (Status 2)',
                'data'    => $cabang
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cabang Not Found',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cabang = Cabang::where('id', $id)->where('status', '!=', 3)->first();

        if ($cabang) {
            $cabang->update([
                'status'     => 3,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang Deleted',
                'log'     => 'Berhasil melakukan penghapusan data cabang (Status 3 - Soft Delete)',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cabang Not Found',
            ], 404);
        }
    }
}
