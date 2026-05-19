<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('qr_templates', 'qr_user_templates');
    }

    public function down(): void
    {
        Schema::rename('qr_user_templates', 'qr_templates');
    }
};
