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
        Schema::create('router_bandwidth_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('router_id');
            $table->string('interface_name', 50)->default('ether1');
            $table->unsignedBigInteger('rx_bytes')->default(0); // Cumulative bytes received (Downstream)
            $table->unsignedBigInteger('tx_bytes')->default(0); // Cumulative bytes transmitted (Upstream)
            $table->unsignedBigInteger('rx_delta_bytes')->default(0); // Delta bytes since last measurement
            $table->unsignedBigInteger('tx_delta_bytes')->default(0); // Delta bytes since last measurement
            $table->unsignedBigInteger('rx_speed_bps')->default(0); // Speed in bps
            $table->unsignedBigInteger('tx_speed_bps')->default(0); // Speed in bps
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['router_id', 'interface_name', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_bandwidth_logs');
    }
};
