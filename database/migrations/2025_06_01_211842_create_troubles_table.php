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
        Schema::create('troubles', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', '64')->unique();
            $table->date('tgl_trouble');
            $table->string('jam_trouble', '6');
            $table->text('problem');
            $table->string('lokasi', '150');
            $table->string('kategori', '50');
            $table->date('tgl_selesai')->nullable();
            $table->string('jam_selesai', '6')->nullable();
            $table->text('solusi')->nullable();
            $table->string('petugas', '50');
            $table->mediumText('foto_awal')->nullable();
            $table->mediumText('foto_akhir')->nullable();
            $table->string('status', '50'); //progress & finished
            $table->string('created_by', '64');
            $table->string('confirmed_by', '64')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troubles');
    }
};
