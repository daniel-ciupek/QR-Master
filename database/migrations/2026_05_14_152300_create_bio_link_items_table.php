<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_link_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bio_link_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 2000);
            $table->string('icon', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->index(['bio_link_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_link_items');
    }
};
