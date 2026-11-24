<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Internal activities (software fixes, server maintenance…) with their own to-dos; notes reuse `comments`. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('internal_activity_todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_activity_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_activity_todos');
        Schema::dropIfExists('internal_activities');
    }
};
