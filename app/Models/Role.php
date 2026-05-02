<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    // Relasi: Satu Role dimiliki oleh banyak User
    public function users()
    {
        return $this->hasMany(User::class);
    }
}