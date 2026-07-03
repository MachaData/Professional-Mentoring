<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Foundational data — always seeded (roles, operator org, program, etc.).
        $this->call([
            RolePermissionSeeder::class,
            CrossPartnersSeeder::class,
            LasBambasProgramSeeder::class,
            MentoringFieldsSeeder::class,
            ResourcesSeeder::class,
            CommunicationsSeeder::class,
        ]);

        // Demo dupla + test accounts (mentor/mentee/coordinador @demo.test with a
        // weak password). Enabled by default; set SEED_DEMO=false in production
        // once real users exist. Also runnable alone: db:seed --class=DemoDuplaSeeder
        if (filter_var(env('SEED_DEMO', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoDuplaSeeder::class);
        }
    }
}
