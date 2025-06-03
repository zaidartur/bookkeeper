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
        Schema::create('petugas', function (Blueprint $table) {
            $table->id();
            $table->string('uuid_petugas', '64')->unique();
            $table->string('nama_petugas', '150');
            $table->string('bidang', '150')->nullable();
            $table->string('nip', '25')->nullable();
            $table->string('alamat')->nullable();
            $table->string('phone', '17')->nullable();
            $table->string('foto_profile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas');
    }
};
