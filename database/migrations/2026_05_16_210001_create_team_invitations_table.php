<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default(TeamRole::Editor->value);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'email']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
