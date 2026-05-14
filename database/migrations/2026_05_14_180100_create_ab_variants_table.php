<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('destination_url', 2000);
            $table->unsignedSmallInteger('weight')->default(50);
            $table->unsignedInteger('scan_count')->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index('ab_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_variants');
    }
};
