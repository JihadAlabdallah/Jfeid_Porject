<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: each user's preferred distance unit ("km" or "miles").
 * Existing users default to "km", which is how the app behaves today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('distance_unit', 10)->default('km')->after('carplate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('distance_unit');
        });
    }
};
