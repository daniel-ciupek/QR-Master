<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // notifications — composite index for unreadNotifications() query
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notifications_morph_read');
        });

        // scan_logs — ip_hash for spam detection queries
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->index('ip_hash', 'idx_scan_logs_ip_hash');
        });

        // affiliate_commissions — FK without auto-index in PostgreSQL
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->index('referred_user_id', 'idx_affiliate_referred_user');
        });

        // qr_user_templates — user_id for dashboard queries
        Schema::table('qr_user_templates', function (Blueprint $table) {
            $table->index('user_id', 'idx_qr_user_templates_user');
        });

        // tags — dedicated user_id index (UNIQUE(user_id, name) doesn't help bare user_id queries)
        Schema::table('tags', function (Blueprint $table) {
            $table->index('user_id', 'idx_tags_user');
        });

        // bio_links — user_id for dashboard queries
        Schema::table('bio_links', function (Blueprint $table) {
            $table->index('user_id', 'idx_bio_links_user');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', fn (Blueprint $t) => $t->dropIndex('idx_notifications_morph_read'));
        Schema::table('scan_logs', fn (Blueprint $t) => $t->dropIndex('idx_scan_logs_ip_hash'));
        Schema::table('affiliate_commissions', fn (Blueprint $t) => $t->dropIndex('idx_affiliate_referred_user'));
        Schema::table('qr_user_templates', fn (Blueprint $t) => $t->dropIndex('idx_qr_user_templates_user'));
        Schema::table('tags', fn (Blueprint $t) => $t->dropIndex('idx_tags_user'));
        Schema::table('bio_links', fn (Blueprint $t) => $t->dropIndex('idx_bio_links_user'));
    }
};
