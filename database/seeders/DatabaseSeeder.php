<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Roles
        $roleAdmin = Role::create(['name' => 'admin', 'description' => 'Administrator Sistem']);
        $roleMerchant = Role::create(['name' => 'merchant', 'description' => 'Kantin atau Merchant']);
        $roleWali = Role::create(['name' => 'wali_santri', 'description' => 'Wali Santri / Orang Tua']);

        // 2. Buat Akun Admin
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@ywir.com',
            'password' => Hash::make('admin123'),
            'role_id' => $roleAdmin->id,
        ]);

        // 3. Buat Akun Wali Santri
        User::create([
            'name' => 'Bapak Fulan',
            'username' => 'fulan_wali',
            'email' => 'fulan@gmail.com',
            'password' => Hash::make('password123'),
            'pin' => Hash::make('123456'),
            'role_id' => $roleWali->id,
        ]);

        // 4. Buat Akun Merchant/Kantin
        User::create([
            'name' => 'Kantin',
            'username' => 'kantin',
            'email' => 'kantin@ywir.com',
            'password' => Hash::make('kantin123'),
            'role_id' => $roleMerchant->id,
        ]);
    }
}