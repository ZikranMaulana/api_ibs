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
        $roleAdmin = Role::create(['kode' => 'ADM001', 'name' => 'Superadmin', 'description' => 'Superadmin Sistem']);
        $roleMerchant = Role::create(['kode' => 'KNT001', 'name' => 'Admin Kantin', 'description' => 'Admin Kantin atau Merchant']);
        $roleWali = Role::create(['kode' => 'WLI001', 'name' => 'Wali Santri', 'description' => 'Wali Santri / Orang Tua']);

        // 2. Buat Akun Admin
        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@ywir.com',
            'password' => Hash::make('superadmin123'),
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
            'name' => 'Admin Kantin',
            'username' => 'admin_kantin',
            'email' => 'adminkantin@ywir.com',
            'password' => Hash::make('kantin123'),
            'role_id' => $roleMerchant->id,
        ]);
    }
}