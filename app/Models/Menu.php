<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'kode', 'nama_menu', 'url', 'icon', 'urutan'];

    // Relasi Many-to-Many ke Role
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }

    // Relasi ke Parent Menu (Menu Atasnya)
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    // Relasi ke Sub-Menus (Anak-anak Menunya)
    public function subMenus()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}