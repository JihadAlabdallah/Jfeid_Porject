<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The per-user KM/Miles setting (added 2026-11-07) is replaced by a unit per
 * car. Any "miles" choice was copied to the user's cars by the previous
 * migration before this column is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'distance_unit')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('distance_unit');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('distance_unit', 10)->default('km')->after('carplate');
        });
    }
};
