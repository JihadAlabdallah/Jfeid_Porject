<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Next change at" reading for every maintenance type, in the record's unit.
 * Filled automatically from the current reading for types with a standard
 * interval (see App\Support\Distance); entered by hand for battery / other.
 * Optional; existing records stay empty. Oil changes keep their own
 * next_change_km as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->unsignedInteger('next_change_reading')->nullable()->after('current_reading');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn('next_change_reading');
        });
    }
};
