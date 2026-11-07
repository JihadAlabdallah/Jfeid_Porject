<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KM or miles per car. Each oil change also keeps the unit its readings were
 * entered in, so a car switching units never changes old records.
 * Existing cars and oil changes default to "km" (how they were entered).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('distance_unit', 10)->default('km')->after('type');
        });

        Schema::table('oil_changes', function (Blueprint $table) {
            $table->string('distance_unit', 10)->default('km')->after('next_change_km');
        });

        // carry over anyone who picked miles with the old per-user setting
        if (Schema::hasColumn('users', 'distance_unit')) {
            DB::table('vehicles')
                ->whereIn('user_id', DB::table('users')->where('distance_unit', 'miles')->select('id'))
                ->update(['distance_unit' => 'miles']);
        }
    }

    public function down(): void
    {
        Schema::table('oil_changes', function (Blueprint $table) {
            $table->dropColumn('distance_unit');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('distance_unit');
        });
    }
};
