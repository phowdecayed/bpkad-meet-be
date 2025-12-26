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
        Schema::table('zoom_meetings', function (Blueprint $table) {
            $table->text('recording_play_url')->nullable()->after('join_url');
            $table->string('recording_passcode')->nullable()->after('recording_play_url');
            $table->longText('summary_content')->nullable()->after('recording_passcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_meetings', function (Blueprint $table) {
            $table->dropColumn(['recording_play_url', 'recording_passcode', 'summary_content']);
        });
    }
};
