<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_clear_role_permissions(): void
    {
        [$admin, $viewer] = $this->createAdminAndViewer();

        Passport::actingAs($admin);

        $this->putJson("/api/roles/{$viewer->id}", [
            'name' => 'viewer',
            'permissions' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.permissions', []);

        $this->assertSame([], $viewer->fresh()->permissions->pluck('name')->all());
    }

    public function test_user_without_roles_manage_cannot_update_role_permissions(): void
    {
        [, $viewer] = $this->createAdminAndViewer();
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->assignRole('viewer');

        Passport::actingAs($outsider);

        $this->putJson("/api/roles/{$viewer->id}", [
            'name' => 'viewer',
            'permissions' => ['competitions.read'],
        ])->assertForbidden();
    }

    private function createAdminAndViewer(): array
    {
        foreach (['competitions.read', 'roles.manage'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'api']);
        }

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->syncPermissions(['roles.manage']);

        $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'api']);
        $viewerRole->syncPermissions(['competitions.read']);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole($viewerRole);

        return [$admin, $viewer];
    }
}
