<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin (Username & Email)
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@ywir.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // 2. Akun Santri (Hanya NIS)
        User::create([
            'name' => 'Ahmad Fulan',
            'nis' => '12345678', 
            'password' => Hash::make('password123'),
            'pin' => Hash::make('123456'),
            'role' => 'santri',
        ]);

        // 3. Akun Merchant / Kantin (Username & Email)
        User::create([
            'name' => 'Kantin',
            'username' => 'kantin',
            'email' => 'kantin@ywir.com',
            'password' => Hash::make('kantin123'),
            'pin' => Hash::make('654321'), // Opsi: Kantin mungkin butuh PIN untuk void/refund
            'role' => 'merchant',
        ]);
    }
}
