<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lembaga extends Model
{
    protected $fillable = [
        'nama_lembaga',
        'alamat',
        'kota',
        'no_telepon',
        'email'
    ];
}
