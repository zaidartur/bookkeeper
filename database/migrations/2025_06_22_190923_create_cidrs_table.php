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
        Schema::create('cidrs', function (Blueprint $table) {
            $table->id();
            $table->integer('cidr')->unique();
            $table->string('subnet_mask', '16');
            $table->string('wildcard_mask', '16');
            $table->integer('total_ip');
            $table->integer('usable_host');
            $table->string('ip_class', '10');
            $table->integer('host_bits');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cidrs');
    }
};
