<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([AdminSeeder::class,CatalogSeeder::class, MembershipPlanSeeder::class, PageSeeder::class]);

        // Demo accounts are for local development and staging only
        if (! app()->isProduction()) {
            $this->call([AdminSeeder::class, DemoMemberSeeder::class, DemoProducerSeeder::class]);
        }
    }
}
