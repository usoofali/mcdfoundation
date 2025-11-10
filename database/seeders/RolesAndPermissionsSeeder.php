<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions by module
        $permissions = [
            'Members' => [
                'view_members' => 'View members list and details',
                'create_members' => 'Create new members',
                'edit_members' => 'Edit member information',
                'delete_members' => 'Delete members',
                'approve_members' => 'Approve member registrations',
            ],
            'Contributions' => [
                'record_contributions' => 'Record member contributions',
                'confirm_contributions' => 'Confirm payment and verify contributions',
                'view_contributions' => 'View contribution records',
                'edit_contributions' => 'Edit contribution records',
                'delete_contributions' => 'Delete contribution records',
                'submit_contributions' => 'Submit contributions with receipt upload (members only)',
            ],
            'Loans' => [
                'apply_loans' => 'Apply for loans (members only)',
                'approve_loans_l1' => 'Approve loans at Level 1 (LG Coordinator)',
                'approve_loans_l2' => 'Approve loans at Level 2 (State Coordinator)',
                'approve_loans_l3' => 'Approve loans at Level 3 (Project Coordinator)',
                'disburse_loans' => 'Disburse approved loans (Treasurer)',
                'view_loans' => 'View loan records',
                'edit_loans' => 'Edit loan records',
            ],
            'Health Claims' => [
                'submit_claims' => 'Submit health claims (members only)',
                'approve_claims' => 'Approve health claims (Health Officer)',
                'pay_claims' => 'Process claim payments (Treasurer)',
                'view_claims' => 'View health claim records',
                'edit_claims' => 'Edit health claim records',
            ],
            'Programs' => [
                'manage_programs' => 'Create and manage vocational programs',
                'enroll_members' => 'Enroll members in programs',
                'issue_certificates' => 'Issue completion certificates',
                'view_programs' => 'View program records',
            ],
            'Reports' => [
                'view_reports' => 'View all reports',
                'export_data' => 'Export data to Excel/CSV',
            ],
            'Administration' => [
                'manage_users' => 'Manage user accounts',
                'manage_roles' => 'Manage roles and permissions',
                'system_settings' => 'Configure system settings',
                'view_audit_logs' => 'View audit logs',
                'manage_healthcare_providers' => 'Manage healthcare providers',
                'manage_contribution_plans' => 'Manage contribution plans',
            ],
        ];

        // Create permissions
        $permissionModels = [];
        foreach ($permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $name => $description) {
                $permissionModels[$name] = Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'module' => $module,
                        'description' => $description,
                    ]
                );
            }
        }

        // Create roles
        $roles = [
            'Super Admin' => [
                'description' => 'Full system access with all permissions',
                'permissions' => array_keys($permissionModels),
            ],
            'System Admin' => [
                'description' => 'System administrator with user management and configuration access',
                'permissions' => [
                    'view_members', 'create_members', 'edit_members', 'approve_members',
                    'view_contributions', 'record_contributions', 'confirm_contributions', 'edit_contributions',
                    'view_loans', 'approve_loans_l1', 'approve_loans_l2', 'approve_loans_l3', 'edit_loans',
                    'view_claims', 'approve_claims', 'edit_claims',
                    'view_programs', 'manage_programs', 'enroll_members', 'issue_certificates',
                    'view_reports', 'export_data',
                    'manage_users', 'manage_roles', 'system_settings', 'view_audit_logs',
                    'manage_healthcare_providers', 'manage_contribution_plans',
                ],
            ],
            'Finance Officer' => [
                'description' => 'Finance officer responsible for financial operations and loan management',
                'permissions' => [
                    'view_members',
                    'view_contributions', 'record_contributions', 'confirm_contributions', 'edit_contributions',
                    'view_loans', 'approve_loans_l1', 'approve_loans_l2', 'approve_loans_l3', 'disburse_loans', 'edit_loans',
                    'view_claims', 'pay_claims',
                    'view_programs',
                    'view_reports', 'export_data',
                ],
            ],
            'Health Officer' => [
                'description' => 'Health officer responsible for eligibility verification and claim approval',
                'permissions' => [
                    'view_members', 'edit_members',
                    'view_contributions', 'submit_contributions',
                    'view_loans',
                    'view_claims', 'approve_claims', 'edit_claims',
                    'view_programs',
                    'view_reports', 'export_data',
                ],
            ],
            'Program Officer' => [
                'description' => 'Program officer responsible for vocational programs and member enrollment',
                'permissions' => [
                    'view_members', 'create_members', 'edit_members',
                    'view_contributions', 'submit_contributions',
                    'view_loans',
                    'view_claims',
                    'view_programs', 'manage_programs', 'enroll_members', 'issue_certificates',
                    'view_reports', 'export_data',
                ],
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['description' => $roleData['description']]
            );

            // Assign permissions to role
            $permissionIds = [];
            foreach ($roleData['permissions'] as $permissionName) {
                if (isset($permissionModels[$permissionName])) {
                    $permissionIds[] = $permissionModels[$permissionName]->id;
                }
            }
            $role->permissions()->sync($permissionIds);
        }
    }
}
