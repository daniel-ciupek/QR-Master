<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->createPartitionedPostgres();
        } else {
            $this->createPlain();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS scan_logs CASCADE');
        } else {
            Schema::dropIfExists('scan_logs');
        }
    }

    /** PostgreSQL: RANGE-partitioned by scanned_at (monthly buckets). */
    private function createPartitionedPostgres(): void
    {
        DB::statement("
            CREATE TABLE scan_logs (
                id            BIGSERIAL,
                qr_code_id    BIGINT        NOT NULL,
                ip_hash       VARCHAR(64)   NOT NULL DEFAULT '',
                country       CHAR(2),
                region        VARCHAR(100),
                city          VARCHAR(100),
                lat           NUMERIC(10, 7),
                lng           NUMERIC(10, 7),
                device_type   VARCHAR(20),
                os            VARCHAR(50),
                browser       VARCHAR(50),
                referrer      VARCHAR(500),
                language      VARCHAR(10),
                scanned_at    TIMESTAMPTZ   NOT NULL,
                PRIMARY KEY (id, scanned_at)
            ) PARTITION BY RANGE (scanned_at)
        ");

        DB::statement('
            ALTER TABLE scan_logs
            ADD CONSTRAINT fk_scan_logs_qr_code_id
            FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE
        ');

        $this->createMonthPartition(now());
        $this->createMonthPartition(now()->addMonth());
        $this->createMonthPartition(now()->addMonths(2));

        DB::statement('CREATE TABLE scan_logs_default PARTITION OF scan_logs DEFAULT');
    }

    /** SQLite / non-PostgreSQL: plain table used for testing. */
    private function createPlain(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64)->default('');
            $table->char('country', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('language', 10)->nullable();
            $table->timestampTz('scanned_at');
        });
    }

    private function createMonthPartition(Carbon $month): void
    {
        $name = 'scan_logs_'.$month->format('Y_m');
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->startOfMonth()->addMonth()->toDateString();

        DB::statement("
            CREATE TABLE IF NOT EXISTS {$name}
            PARTITION OF scan_logs
            FOR VALUES FROM ('{$start}') TO ('{$end}')
        ");
    }
};
