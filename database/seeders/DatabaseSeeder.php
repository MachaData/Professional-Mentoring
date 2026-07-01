<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CrossPartnersSeeder::class,
            LasBambasProgramSeeder::class,
            MentoringFieldsSeeder::class,
            ResourcesSeeder::class,
            DemoDuplaSeeder::class,
        ]);
    }
}
