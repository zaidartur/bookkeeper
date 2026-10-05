<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('troubles', function (Blueprint $table) {
            $table->index(['kategori', 'status'], 'idx_troubles_kategori_status');
            $table->index('tgl_trouble', 'idx_troubles_tgl_trouble');
        });

        Schema::table('buku_tamus', function (Blueprint $table) {
            $table->index('tanggal', 'idx_buku_tamus_tanggal');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->index('uid_category', 'idx_inventories_uid_category');
            $table->index('status', 'idx_inventories_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('troubles', function (Blueprint $table) {
            $table->dropIndex('idx_troubles_kategori_status');
            $table->dropIndex('idx_troubles_tgl_trouble');
        });

        Schema::table('buku_tamus', function (Blueprint $table) {
            $table->dropIndex('idx_buku_tamus_tanggal');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('idx_inventories_uid_category');
            $table->dropIndex('idx_inventories_status');
        });
    }
};
