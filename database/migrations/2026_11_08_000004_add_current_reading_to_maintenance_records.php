<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Odometer reading for every maintenance record, with the unit (km / miles)
 * it was entered in. Optional; existing records stay empty.
 * Oil changes keep their own current_km / next_change_km as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->unsignedInteger('current_reading')->nullable()->after('type');
            $table->string('distance_unit', 10)->nullable()->after('current_reading');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['current_reading', 'distance_unit']);
        });
    }
};
