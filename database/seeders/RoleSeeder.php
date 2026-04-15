<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie necesita los roles en ambos guards
        foreach (['web', 'api'] as $guard) {
            Role::firstOrCreate(['name' => 'student', 'guard_name' => $guard]);
            Role::firstOrCreate(['name' => 'trainer', 'guard_name' => $guard]);
        }

        $this->command->info('Roles creados: student y trainer (guards: web, api)');
    }
}
