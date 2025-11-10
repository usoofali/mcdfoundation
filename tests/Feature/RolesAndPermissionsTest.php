<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_roles_and_permissions_are_seeded_correctly(): void
    {
        // Check that all 5 core roles exist
        $this->assertEquals(5, Role::count());

        $expectedRoles = ['Super Admin', 'System Admin', 'Finance Officer', 'Health Officer', 'Program Officer'];
        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(Role::where('name', $roleName)->exists());
        }

        // Check that permissions exist
        $this->assertGreaterThan(0, Permission::count());

        // Check that Super Admin has all permissions
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $this->assertEquals(Permission::count(), $superAdmin->permissions->count());
    }

    public function test_user_can_check_permissions_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Health Officer')->first();
        $user->role()->associate($role);
        $user->save();

        // Health Officer should have view_members permission
        $this->assertTrue($user->hasPermission('view_members'));

        // Health Officer should not have manage_users permission
        $this->assertFalse($user->hasPermission('manage_users'));

        // Health Officer should have approve_claims permission
        $this->assertTrue($user->hasPermission('approve_claims'));
    }

    public function test_user_can_check_roles_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Finance Officer')->first();
        $user->role()->associate($role);
        $user->save();

        $this->assertTrue($user->hasRole('Finance Officer'));
        $this->assertFalse($user->hasRole('Super Admin'));
    }

    public function test_member_policy_works_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Program Officer')->first();
        $user->role()->associate($role);
        $user->save();

        $member = Member::factory()->create();

        // Program Officer should be able to view and create members
        $this->assertTrue($user->can('view', $member));
        $this->assertTrue($user->can('create', Member::class));

        // Program Officer should not be able to delete members
        $this->assertFalse($user->can('delete', $member));
    }

    public function test_contribution_policy_works_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Finance Officer')->first();
        $user->role()->associate($role);
        $user->save();

        $contribution = Contribution::factory()->create();

        // Finance Officer should be able to view and confirm contributions
        $this->assertTrue($user->can('view', $contribution));
        $this->assertTrue($user->can('confirm', $contribution));

        // Finance Officer should be able to record contributions
        $this->assertTrue($user->can('create', Contribution::class));
    }

    public function test_loan_policy_works_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'Finance Officer')->first();
        $user->role()->associate($role);
        $user->save();

        $loan = Loan::factory()->create();

        // Finance Officer should be able to view loans and disburse them
        $this->assertTrue($user->can('view', $loan));
        $this->assertTrue($user->can('disburse', $loan));

        // Finance Officer should be able to approve at all levels
        $this->assertTrue($user->can('approveL1', $loan));
        $this->assertTrue($user->can('approveL2', $loan));
        $this->assertTrue($user->can('approveL3', $loan));
    }

    public function test_user_policy_prevents_self_deletion(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'System Admin')->first();
        $user->role()->associate($role);
        $user->save();

        // System Admin should not be able to delete themselves
        $this->assertFalse($user->can('delete', $user));

        // But should be able to delete other users
        $otherUser = User::factory()->create();
        $this->assertTrue($user->can('delete', $otherUser));
    }
}
