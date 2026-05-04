<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Master Menu
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            $table->string('kode', 20)->unique();
            $table->string('nama_menu');
            $table->string('url')->nullable(); // contoh: /dashboard, /keuangan
            $table->string('icon')->nullable(); // contoh: fi-rr-home
            $table->integer('urutan')->default(0); // Untuk mengurutkan menu di sidebar
            $table->timestamps();
        });

        // 2. Tabel Pivot (Penghubung Menu dan Role)
        Schema::create('menu_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_role');
        Schema::dropIfExists('menus');
    }
};