<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 40)->unique();
            $table->string('ip_address', 45)->nullable(); // PII — covered by purgeUserData() in Etap 10
            $table->text('user_agent')->nullable();
            $table->timestamp('last_active_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'last_active_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
