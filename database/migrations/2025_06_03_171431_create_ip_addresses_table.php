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
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', '40')->unique();
            $table->string('network_ip', '16')->unique();
            $table->string('subnet_mask', '16');
            $table->integer('cidr');
            $table->integer('total_ip');
            $table->integer('usable_hosts');
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
        Schema::dropIfExists('ip_addresses');
    }
};
