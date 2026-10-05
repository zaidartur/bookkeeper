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
        Schema::table('troubles', function (Blueprint $table) {
            $table->unsignedInteger('durasi_menit')->nullable()->after('jam_selesai');
            $table->string('sla_status', 20)->nullable()->after('durasi_menit'); // compliant, breached
            $table->timestamp('target_selesai')->nullable()->after('sla_status');

            $table->index(['status', 'sla_status']);
            $table->index('durasi_menit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('troubles', function (Blueprint $table) {
            $table->dropIndex(['status', 'sla_status']);
            $table->dropIndex(['durasi_menit']);
            $table->dropColumn(['durasi_menit', 'sla_status', 'target_selesai']);
        });
    }
};
