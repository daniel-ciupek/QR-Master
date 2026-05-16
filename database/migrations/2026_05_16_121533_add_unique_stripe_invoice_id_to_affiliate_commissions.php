<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table): void {
            $table->unique('stripe_invoice_id', 'unique_stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table): void {
            $table->dropUnique('unique_stripe_invoice_id');
        });
    }
};
