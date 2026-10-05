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
        Schema::table('ip_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('ip_assignments', 'mac_address')) {
                $table->string('mac_address', 30)->nullable()->after('status');
            }
            if (!Schema::hasColumn('ip_assignments', 'hostname')) {
                $table->string('hostname', 100)->nullable()->after('mac_address');
            }
            if (!Schema::hasColumn('ip_assignments', 'source')) {
                $table->string('source', 50)->default('manual')->after('hostname');
            }
            if (!Schema::hasColumn('ip_assignments', 'last_seen')) {
                $table->timestamp('last_seen')->nullable()->after('source');
            }
            $table->index('uuid_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_assignments', function (Blueprint $table) {
            $table->dropIndex(['uuid_ip']);
            $table->dropColumn(['mac_address', 'hostname', 'source', 'last_seen']);
        });
    }
};
