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
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->string('merek_barang')->nullable();
            $table->string('no_seri_barang')->nullable();
            $table->string('ukuran_barang')->nullable();
            $table->string('bahan')->nullable();
            $table->integer('jumlah_barang');
            $table->string('satuan')->nullable();
            $table->integer('tahun_beli');
            $table->text('kondisi_barang')->nullable();
            $table->string('keterangan');
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
