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
            // Add the foreign key to the new meetings table
            $table->foreignId('meeting_id')->after('id')->constrained()->onDelete('cascade');

            // Make zoom-specific columns nullable, as they won't exist for offline meetings
            $table->string('uuid')->nullable()->change();
            $table->string('host_id')->nullable()->change();
            $table->string('host_email')->nullable()->change();
            $table->integer('type')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->timestamp('start_time')->nullable()->change();
            $table->integer('duration')->nullable()->change();
            $table->string('timezone')->nullable()->change();
            $table->timestamp('created_at_zoom')->nullable()->change();
            $table->text('start_url')->nullable()->change();
            $table->text('join_url')->nullable()->change();

            // Drop the redundant columns
            $table->dropColumn(['topic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_meetings', function (Blueprint $table) {
            // Add the 'topic' column back
            $table->string('topic')->after('host_email');

            // Revert the nullable changes
            $table->string('uuid')->nullable(false)->change();
            $table->string('host_id')->nullable(false)->change();
            $table->string('host_email')->nullable(false)->change();
            $table->integer('type')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
            $table->timestamp('start_time')->nullable(false)->change();
            $table->integer('duration')->nullable(false)->change();
            $table->string('timezone')->nullable(false)->change();
            $table->timestamp('created_at_zoom')->nullable(false)->change();
            $table->text('start_url')->nullable(false)->change();
            $table->text('join_url')->nullable(false)->change();

            // Drop the foreign key
            $table->dropForeign(['meeting_id']);
            $table->dropColumn('meeting_id');
        });
    }
};