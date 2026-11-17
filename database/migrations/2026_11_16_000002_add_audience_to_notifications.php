<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification history: who it was for and how many phones got it.
 * Optional columns only; old rows stay as they are (audience empty = topic send).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('audience', 20)->nullable()->after('topic');
            $table->json('recipients')->nullable()->after('audience');
            $table->unsignedInteger('sent_count')->nullable()->after('response');
            $table->unsignedInteger('failed_count')->nullable()->after('sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['audience', 'recipients', 'sent_count', 'failed_count']);
        });
    }
};
