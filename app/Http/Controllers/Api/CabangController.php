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
    public function index()
    {
        $cabangs = Cabang::all();
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
            'nama_cabang' => 'required',
            'lokasi'      => 'required'
        ]);

        $cabang = Cabang::create([
            'nama_cabang' => $request->nama_cabang,
            'lokasi'      => $request->lokasi
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cabang Created',
            'data'    => $cabang
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cabang = Cabang::find($id);

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
            'nama_cabang' => 'required',
            'lokasi'      => 'required'
        ]);

        $cabang = Cabang::find($id);

        if ($cabang) {
            $cabang->update([
                'nama_cabang' => $request->nama_cabang,
                'lokasi'      => $request->lokasi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang Updated',
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
        $cabang = Cabang::find($id);

        if ($cabang) {
            $cabang->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cabang Deleted',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cabang Not Found',
            ], 404);
        }
    }
}
