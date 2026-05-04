<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            if (!Schema::hasColumn('menus', 'parent_id')) {
                // Menambahkan kolom parent_id yang merelasikan ke tabel menus itu sendiri
                // Posisinya diletakkan setelah kolom 'id' agar rapi di database
                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('menus')
                      ->onDelete('cascade')
                      ->after('id'); 
            }
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Wajib drop foreign key dulu, baru drop kolomnya
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};