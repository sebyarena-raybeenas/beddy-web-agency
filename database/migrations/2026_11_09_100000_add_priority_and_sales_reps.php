<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Projects get a priority level and an optional reference salesperson. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_reps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('priority')->default('normale')->after('status');
            $table->foreignId('sales_rep_id')->nullable()->after('owner_id')->constrained('sales_reps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_rep_id');
            $table->dropColumn('priority');
        });

        Schema::dropIfExists('sales_reps');
    }
};
