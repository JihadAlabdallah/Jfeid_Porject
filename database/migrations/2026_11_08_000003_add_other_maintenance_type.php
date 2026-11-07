<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New maintenance type "other" for services outside the fixed list, with its
 * own details table. Price and employee live on maintenance_records already.
 *
 * maintenance_records.type has a database rule listing the allowed types;
 * it is rewritten to also allow "other". No rows are changed.
 */
return new class extends Migration
{
    private const OLD_TYPES = [
        'oil_change', 'steering_oil_change', 'battery_service', 'wheel_service',
        'transmission_oil_change', 'differential_oil_change', 'gear_oil_change',
    ];

    public function up(): void
    {
        $this->setAllowedTypes([...self::OLD_TYPES, 'other']);

        Schema::create('other_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('maintenance_records')->onDelete('cascade');
            $table->string('name');
            $table->string('category')->nullable();   // shown as "Type", free text
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_services');
        // fails on purpose if "other" records exist, so they are never lost silently
        $this->setAllowedTypes(self::OLD_TYPES);
    }

    private function setAllowedTypes(array $types): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = implode(', ', array_map(fn ($t) => "'" . $t . "'", $types));
            DB::statement('ALTER TABLE maintenance_records DROP CONSTRAINT IF EXISTS maintenance_records_type_check');
            DB::statement("ALTER TABLE maintenance_records ADD CONSTRAINT maintenance_records_type_check CHECK (type IN ($list))");

            return;
        }

        // other databases (SQLite in the test suite): rebuild the column
        Schema::table('maintenance_records', function (Blueprint $table) use ($types) {
            $table->enum('type', $types)->change();
        });
    }
};
