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
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id(); // Standard auto-incrementing primary key
            $table->unsignedBigInteger('zoom_id')->unique()->comment('The ID from Zoom API');
            $table->string('uuid')->unique();
            $table->string('host_id');
            $table->string('host_email');
            $table->string('topic');
            $table->integer('type');
            $table->string('status');
            $table->timestamp('start_time')->nullable();
            $table->integer('duration');
            $table->string('timezone');
            $table->timestamp('created_at_zoom')->comment('Timestamp from Zoom API');
            $table->text('start_url');
            $table->text('join_url');
            $table->string('password')->nullable();
            $table->json('settings');
            $table->timestamps(); // Laravel's created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_meetings');
    }
};
