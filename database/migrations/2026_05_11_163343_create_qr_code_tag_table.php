<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_tag', function (Blueprint $table) {
            $table->foreignId('qr_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['qr_code_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_tag');
    }
};
