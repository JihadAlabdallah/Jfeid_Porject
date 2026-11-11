<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Car plate per vehicle (a client can have several cars).
 * Existing plates are copied from the user to their oldest car; the user's
 * own carplate column is kept unchanged for the current app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('carplate')->nullable()->after('type');
        });

        $users = DB::table('users')->whereNotNull('carplate')->where('carplate', '!=', '')->get(['id', 'carplate']);

        foreach ($users as $user) {
            $oldest = DB::table('vehicles')->where('user_id', $user->id)->orderBy('id')->value('id');

            if ($oldest) {
                DB::table('vehicles')->where('id', $oldest)->update(['carplate' => $user->carplate]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('carplate');
        });
    }
};
