<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 253)->unique();
            $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
            $table->string('verification_token', 64)->unique();
            $table->timestamp('verified_at')->nullable();
            $table->string('ssl_status', 32)->default('none');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_domains');
    }
};
