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
            // Used in scopeActive() alongside expires_at (which already has an index)
            $table->index('activates_at');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table): void {
            $table->dropIndex(['activates_at']);
        });
    }
};
