<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 32)->default('url');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_hash', 12)->unique();

            $table->text('destination_url')->nullable();

            // JSONB for dot styles, colors, logo settings etc. (Etap 4+)
            $table->jsonb('settings')->default('{}');

            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index();

            // Argon2id hash — null means no password required
            $table->string('password_hash')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common dashboard queries
            $table->index(['user_id', 'is_active', 'created_at']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
