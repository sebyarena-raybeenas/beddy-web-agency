<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting and assistance belong to a single site, not to the client: they move to projects
 * (each project inherits its client's current values). Projects also get a payment status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('payment_status')->default('non_pagato')->after('status');
            $table->string('hosting_status')->nullable()->after('payment_status');
            $table->boolean('has_assistance')->default(false)->after('hosting_status');
            $table->string('assistance_type')->nullable()->after('has_assistance');
        });

        DB::table('clients')->orderBy('id')->each(function (object $client) {
            DB::table('projects')->where('client_id', $client->id)->update([
                'hosting_status' => $client->hosting_status,
                'has_assistance' => (bool) $client->has_assistance,
                'assistance_type' => $client->assistance_type,
            ]);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['has_assistance', 'assistance_type', 'hosting_status']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('has_assistance')->default(false);
            $table->string('assistance_type')->nullable();
            $table->string('hosting_status')->nullable();
        });

        DB::table('clients')->orderBy('id')->each(function (object $client) {
            $project = DB::table('projects')->where('client_id', $client->id)->orderBy('id')->first();

            if ($project) {
                DB::table('clients')->where('id', $client->id)->update([
                    'hosting_status' => $project->hosting_status,
                    'has_assistance' => (bool) $project->has_assistance,
                    'assistance_type' => $project->assistance_type,
                ]);
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'hosting_status', 'has_assistance', 'assistance_type']);
        });
    }
};
