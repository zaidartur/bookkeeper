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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', '64')->unique();
            $table->date('tanggal_mulai');
            $table->string('jam_mulai');
            $table->string('judul');
            $table->string('deskripsi')->nullable();
            $table->text('lokasi');
            $table->text('alur_perawatan')->nullable();
            $table->text('problem')->nullable();
            $table->text('petugas');
            $table->mediumText('foto_sebelum')->nullable();
            $table->mediumText('foto_setelah')->nullable();
            $table->string('user_id', '64');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
