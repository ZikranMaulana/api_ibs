<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->after('description')->comment('1 = Aktif, 0 = Tidak Aktif');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
