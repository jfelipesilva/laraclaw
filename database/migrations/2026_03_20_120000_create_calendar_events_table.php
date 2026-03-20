<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('google_event_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('start_at')->index();
            $table->timestamp('end_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('status')->default('confirmed'); // confirmed, tentative, cancelled
            $table->string('calendar_name')->nullable();
            $table->string('color')->nullable();
            $table->string('html_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
