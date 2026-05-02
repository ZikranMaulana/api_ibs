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
        'email',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater() {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter() {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
