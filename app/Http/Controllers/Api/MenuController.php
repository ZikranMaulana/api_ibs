<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    /**
     * Get Daftar Menu Tersusun Rapi (Utama & Sub-menu)
     */
    public function index(Request $request)
    {
        $currentUser = $request->user()->load('role');
        $roleId = $currentUser->role_id;
        
        if ($currentUser->role->kode === 'ADM001') {
            // SUPERADMIN: Ambil SEMUA menu utama (parent_id null), beserta sub-menu dan role-nya
            $menus = Menu::with(['roles', 'subMenus.roles'])
                         ->whereNull('parent_id')
                         ->orderBy('urutan', 'asc')
                         ->get();
        } else {
            // ROLE LAIN: Ambil menu utama yang diizinkan untuk role ini, 
            // BESERTA sub-menu yang JUGA diizinkan untuk role ini.
            $menus = $currentUser->role->menus()
                ->whereNull('parent_id')
                ->with(['subMenus' => function($query) use ($roleId) {
                    $query->whereHas('roles', function($q) use ($roleId) {
                        $q->where('roles.id', $roleId);
                    })->orderBy('urutan', 'asc');
                }])
                ->orderBy('urutan', 'asc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar menu',
            'data' => $menus
        ], 200);
    }

    /**
     * Tampilkan Detail Satu Menu
     */
    public function show($id_or_kode)
    {
        // Load data parent dan sub-menu nya sekalian
        $menu = Menu::with(['roles', 'parent', 'subMenus'])->where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();

        if (!$menu) return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);

        return response()->json(['status' => 'success', 'data' => $menu], 200);
    }

    /**
     * FUNGSI BANTUAN 1: Cek & Validasi Role (Seperti sebelumnya)
     */
    private function resolveRoleIds($rolesInput)
    {
        if (!is_array($rolesInput) || empty($rolesInput)) return [];
        $rolesInput = array_unique($rolesInput);
        $numericRoles = array_filter($rolesInput, 'is_numeric');
        $stringRoles = array_filter($rolesInput, function ($val) { return !is_numeric($val); });

        $foundRoles = Role::whereIn('id', $numericRoles)->orWhereIn('kode', $stringRoles)->get();

        if ($foundRoles->count() !== count($rolesInput)) {
            $invalidInputs = array_diff($rolesInput, $foundRoles->pluck('id')->toArray(), $foundRoles->pluck('kode')->toArray());
            throw ValidationException::withMessages([
                'roles' => 'Role tidak ditemukan: ' . implode(', ', $invalidInputs)
            ]);
        }
        return $foundRoles->pluck('id')->toArray();
    }

    /**
     * FUNGSI BANTUAN 2: Cari Parent Menu berdasarkan ID atau Kode
     */
    private function resolveParentId($parentIdInput)
    {
        if (empty($parentIdInput)) return null;

        $parent = Menu::where('id', $parentIdInput)->orWhere('kode', $parentIdInput)->first();
        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Menu Parent dengan ID/Kode "'.$parentIdInput.'" tidak ditemukan.'
            ]);
        }
        return $parent->id;
    }

    /**
     * Tambah Menu Baru
     */
    public function store(Request $request)
    {
        if (auth()->user()->role->kode !== 'ADM001') {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'parent_id' => 'nullable', // Bisa diisi ID atau Kode
            'kode'      => 'required|string|max:20|unique:menus,kode',
            'nama_menu' => 'required|string',
            'url'       => 'nullable|string',
            'icon'      => 'nullable|string',
            'urutan'    => 'nullable|integer',
            'roles'     => 'required|array|min:1', 
        ]);

        $dataMenu = $request->only(['kode', 'nama_menu', 'url', 'icon', 'urutan']);
        
        // Cek parent_id (Jika diisi, cari ID aslinya)
        $dataMenu['parent_id'] = $this->resolveParentId($request->parent_id);

        $menu = Menu::create($dataMenu);

        $validRoleIds = $this->resolveRoleIds($request->roles);
        $menu->roles()->attach($validRoleIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Menu berhasil dibuat.',
            'data' => $menu->load('roles', 'parent')
        ], 201);
    }

    /**
     * Update Menu
     */
    public function update(Request $request, $id_or_kode)
    {
        if (auth()->user()->role->kode !== 'ADM001') return response()->json(['status' => 'error'], 403);

        $menu = Menu::where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();
        if (!$menu) return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);

        $request->validate([
            'parent_id' => 'nullable',
            'kode'      => 'sometimes|required|string|max:20|unique:menus,kode,' . $menu->id,
            'nama_menu' => 'sometimes|required|string',
            'url'       => 'nullable|string',
            'icon'      => 'nullable|string',
            'urutan'    => 'nullable|integer',
            'roles'     => 'sometimes|required|array|min:1', 
        ]);

        $dataMenu = $request->only(['kode', 'nama_menu', 'url', 'icon', 'urutan']);
        
        if ($request->has('parent_id')) {
            $dataMenu['parent_id'] = $this->resolveParentId($request->parent_id);
            // Cegah error logika: Menu tidak boleh menjadi parent untuk dirinya sendiri
            if ($dataMenu['parent_id'] === $menu->id) {
                return response()->json(['status' => 'error', 'message' => 'Menu tidak bisa menjadi parent bagi dirinya sendiri.'], 422);
            }
        }

        $menu->update($dataMenu);

        if ($request->has('roles')) {
            $validRoleIds = $this->resolveRoleIds($request->roles);
            $menu->roles()->sync($validRoleIds);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Menu berhasil diupdate.',
            'data' => $menu->load('roles', 'parent')
        ], 200);
    }

    /**
     * Hapus Menu
     */
    public function destroy($id_or_kode)
    {
        if (auth()->user()->role->kode !== 'ADM001') return response()->json(['status' => 'error'], 403);

        $menu = Menu::where('id', $id_or_kode)->orWhere('kode', $id_or_kode)->first();
        if (!$menu) return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan'], 404);

        // Karena kita pasang onDelete('cascade') di database, jika Parent dihapus, 
        // maka Sub-menunya juga akan otomatis terhapus dengan sendirinya!
        $menu->delete();

        return response()->json(['status' => 'success', 'message' => 'Menu dihapus permanen.'], 200);
    }
}