<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('flow');
            $table->string('label');
            $table->boolean('is_mandatory')->default(true);
            $table->string('material_kind')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['flow', 'label']);
        });

        Schema::create('project_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('requirement_templates')->nullOnDelete();
            $table->string('label');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('project_requirements')->nullOnDelete();
            $table->string('kind');
            $table->string('status')->default('atteso');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('path');
            $table->string('filename');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('project_requirements');
        Schema::dropIfExists('requirement_templates');
    }
};
