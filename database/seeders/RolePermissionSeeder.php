<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            User::ROLE_SUPERADMIN,
            User::ROLE_ORG_ADMIN,
            User::ROLE_FACILITATOR,
            User::ROLE_PARTICIPANT,
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
