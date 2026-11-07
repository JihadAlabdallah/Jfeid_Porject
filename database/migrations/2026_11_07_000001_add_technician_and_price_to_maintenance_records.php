<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: two optional columns. Existing records keep all their data
 * and simply have no technician / price until an admin fills them in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->string('technician_name')->nullable()->after('type');
            $table->decimal('price', 10, 2)->nullable()->after('technician_name');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['technician_name', 'price']);
        });
    }
};
