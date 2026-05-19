<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_code_id')->unique()->constrained('qr_codes')->cascadeOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('title');
            $table->text('bio')->nullable();
            $table->string('template', 30)->default('minimal');
            $table->jsonb('theme')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_links');
    }
};
