<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50);
            $table->enum('status', ['operational', 'degraded', 'outage'])->default('operational');
            $table->unsignedSmallInteger('response_time_ms')->nullable();
            $table->string('message', 255)->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->index(['service', 'checked_at']);
            $table->index('checked_at');
        });

        Schema::create('status_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('severity', ['minor', 'major', 'critical'])->default('minor');
            $table->enum('status', ['investigating', 'identified', 'monitoring', 'resolved'])->default('investigating');
            $table->boolean('is_maintenance')->default(false);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_incidents');
        Schema::dropIfExists('status_checks');
    }
};
