<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('email_domain', 253);
            $table->text('client_id');
            $table->text('client_secret');
            $table->string('tenant_id', 253)->nullable();
            $table->string('okta_domain', 253)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_provision')->default(false);
            $table->timestamps();

            $table->index(['email_domain', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_connections');
    }
};
