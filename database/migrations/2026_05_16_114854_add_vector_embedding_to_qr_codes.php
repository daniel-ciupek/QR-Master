<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::statement('ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS embedding vector(768)');
            DB::statement('CREATE INDEX IF NOT EXISTS qr_codes_embedding_idx ON qr_codes USING ivfflat (embedding vector_cosine_ops) WITH (lists = 50)');
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (! str_contains($msg, 'does not exist')
                && ! str_contains($msg, 'already exists')
                && ! str_contains($msg, 'is not available')
            ) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS qr_codes_embedding_idx');
            DB::statement('ALTER TABLE qr_codes DROP COLUMN IF EXISTS embedding');
        } catch (Exception $e) {
            if (! str_contains($e->getMessage(), 'does not exist')) {
                throw $e;
            }
        }
    }
};
