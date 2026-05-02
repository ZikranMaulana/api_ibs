<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['kode', 'name', 'description', 'status', 'created_by', 'updated_by', 'deleted_by'];

    // Relasi: Satu Role dimiliki oleh banyak User
    public function users()
    {
        return $this->hasMany(User::class);
    }

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