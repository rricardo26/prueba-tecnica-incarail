<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'competitions.read',
            'competitions.import',
            'teams.read',
            'players.read',
            'users.manage',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->syncPermissions($permissions);

        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'api']);
        $viewerRole->syncPermissions([
            'competitions.read',
            'teams.read',
            'players.read',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@incarail.pe'],
            ['name' => 'Administrador IncaRail', 'password' => 'Password123!', 'is_active' => true]
        );
        $admin->assignRole($adminRole);

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@incarail.pe'],
            ['name' => 'Analista Consulta', 'password' => 'Password123!', 'is_active' => true]
        );
        $viewer->assignRole($viewerRole);
    }
}
