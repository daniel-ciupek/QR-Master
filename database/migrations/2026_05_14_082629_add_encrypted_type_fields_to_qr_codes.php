<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            // vCard PII — encrypted at rest (Eloquent cast 'encrypted')
            $table->text('vcard_phone')->nullable()->after('settings');
            $table->text('vcard_email')->nullable()->after('vcard_phone');

            // WiFi credentials — encrypted at rest
            $table->text('wifi_password')->nullable()->after('vcard_email');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropColumn(['vcard_phone', 'vcard_email', 'wifi_password']);
        });
    }
};
