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
        Schema::create('executions', function (Blueprint $table) {
            $table->id();
            $table->string('agent_slug')->nullable()->index();
            $table->enum('status', ['running', 'success', 'error'])->default('running')->index();
            $table->text('prompt');
            $table->text('system_prompt')->nullable();
            $table->string('directory')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->unsignedSmallInteger('num_turns')->nullable();
            $table->string('session_id')->nullable();
            $table->longText('output_result')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executions');
    }
};
