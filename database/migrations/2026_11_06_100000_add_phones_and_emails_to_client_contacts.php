<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A contact can have several phone numbers and email addresses. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->json('phones')->nullable()->after('role');
            $table->json('emails')->nullable()->after('phones');
        });

        DB::table('client_contacts')->orderBy('id')->each(function (object $row) {
            DB::table('client_contacts')->where('id', $row->id)->update([
                'phones' => json_encode(array_values(array_filter([$row->phone]))),
                'emails' => json_encode(array_values(array_filter([$row->email]))),
            ]);
        });

        Schema::table('client_contacts', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('role');
            $table->string('email')->nullable()->after('phone');
        });

        DB::table('client_contacts')->orderBy('id')->each(function (object $row) {
            DB::table('client_contacts')->where('id', $row->id)->update([
                'phone' => json_decode($row->phones ?? '[]', true)[0] ?? null,
                'email' => json_decode($row->emails ?? '[]', true)[0] ?? null,
            ]);
        });

        Schema::table('client_contacts', function (Blueprint $table) {
            $table->dropColumn(['phones', 'emails']);
        });
    }
};
