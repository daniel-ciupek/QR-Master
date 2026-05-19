<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->foreignId('team_id')
                ->nullable()
                ->after('user_id')
                ->constrained('teams')
                ->nullOnDelete();

            $table->index(['team_id', 'is_active', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'is_active', 'created_at']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
