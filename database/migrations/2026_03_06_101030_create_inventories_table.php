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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('uid', '40')->unique();
            $table->string('uid_category', '40');
            $table->string('uid_brand', '40');
            $table->string('type')->nullable();
            $table->string('serial')->nullable();
            $table->date('date_in');
            $table->enum('method', ['pengadaan', 'pemeliharaan']);
            $table->enum('status', ['idle', 'backup', 'terpasang']);
            $table->string('uid_location', '40');
            $table->string('condition');
            $table->string('notes')->nullable();
            $table->string('user_id', '40');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
