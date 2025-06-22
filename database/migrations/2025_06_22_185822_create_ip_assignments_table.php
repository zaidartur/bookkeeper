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
        Schema::create('ip_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid_ip', '40');
            $table->string('assigned_ip', '16')->unique();
            $table->string('device', '100');
            $table->string('kategori', '100');
            $table->string('status', '50')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('user_id', '40');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_assignments');
    }
};
