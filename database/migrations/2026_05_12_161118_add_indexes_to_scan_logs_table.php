<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On a partitioned PostgreSQL table, indexes on the parent are automatically
        // propagated to all existing and future partitions (PG 11+).
        Schema::table('scan_logs', function (Blueprint $table) {
            // Primary analytics queries: scans per QR code over time
            $table->index(['qr_code_id', 'scanned_at'], 'idx_scan_logs_qr_time');

            // Geo breakdown: scans by country in a time range
            $table->index(['scanned_at', 'country'], 'idx_scan_logs_time_country');

            // Device / OS / browser breakdown per QR code
            $table->index(['qr_code_id', 'device_type'], 'idx_scan_logs_qr_device');
        });
    }

    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->dropIndex('idx_scan_logs_qr_time');
            $table->dropIndex('idx_scan_logs_time_country');
            $table->dropIndex('idx_scan_logs_qr_device');
        });
    }
};
