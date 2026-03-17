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
        Schema::create('clickup_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('clickup_id')->unique();
            $table->string('name');
            $table->string('status')->index();
            $table->string('status_color')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable()->index();
            $table->string('assignee_name')->nullable();
            $table->string('priority')->nullable();
            $table->string('project')->nullable();
            $table->timestamp('due_date')->nullable()->index();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_done')->nullable();
            $table->string('url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clickup_tasks');
    }
};
