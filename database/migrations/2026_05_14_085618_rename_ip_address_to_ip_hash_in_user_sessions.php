<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            // Replace plain-text IP with HMAC-SHA256 hash (GDPR compliance)
            $table->dropColumn('ip_address');
            $table->string('ip_hash', 64)->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn('ip_hash');
            $table->string('ip_address', 45)->nullable()->after('session_id');
        });
    }
};
