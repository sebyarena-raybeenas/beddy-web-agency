<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled on purpose: the demo data goes through the
     * observers and domain services that enforce the workflow rules.
     */
    public function run(): void
    {
        $this->call(TemplateSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
