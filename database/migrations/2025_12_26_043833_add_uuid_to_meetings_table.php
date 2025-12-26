<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Populate existing records to avoid unique constraint violation if any
        // In a real prod environment with data, we'd do this carefully.
        // Assuming dev/staging or small data for now.
        $ids = \Illuminate\Support\Facades\DB::table('meetings')->pluck('id');
        foreach ($ids as $id) {
            \Illuminate\Support\Facades\DB::table('meetings')
                ->where('id', $id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
        }

        // Make it not nullable after population
        Schema::table('meetings', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
