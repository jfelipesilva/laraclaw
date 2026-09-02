<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clickup_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->after('assignee_name');
            $table->string('creator_name')->nullable()->after('creator_id');
        });
    }

    public function down(): void
    {
        Schema::table('clickup_tasks', function (Blueprint $table) {
            $table->dropColumn(['creator_id', 'creator_name']);
        });
    }
};
