<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirect_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->jsonb('conditions')->default('[]');
            $table->string('destination_url', 2000);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['qr_code_id', 'priority', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirect_rules');
    }
};
