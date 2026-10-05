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
        Schema::create('network_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('ip_address', 45);
            $table->unsignedInteger('port')->nullable();
            $table->string('type', 50)->default('router'); // router, gateway, server, switch, access_point, cctv
            $table->string('status', 20)->default('PENDING'); // UP, DOWN, PENDING
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_change_at')->nullable();
            $table->unsignedInteger('fail_count')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_telegram')->default(true);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'status']);
            $table->index('ip_address');
        });

        Schema::create('network_uptime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_monitor_id')->constrained('network_monitors')->onDelete('cascade');
            $table->string('status', 20); // UP, DOWN
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->index(['network_monitor_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_uptime_logs');
        Schema::dropIfExists('network_monitors');
    }
};
