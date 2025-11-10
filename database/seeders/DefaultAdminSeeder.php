<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Get Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if (! $superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RolesAndPermissionsSeeder first.');

            return;
        }

        // Create default super admin user
        User::updateOrCreate(
            ['email' => 'admin@mcdf.org'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@mcdf.org',
                'password' => Hash::make('password123'),
                'role_id' => $superAdminRole->id,
                'phone' => '+2348000000000',
                'address' => 'MCDF Headquarters',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Default admin user created successfully.');
        $this->command->info('Email: admin@mcdf.org');
        $this->command->info('Password: password123');
        $this->command->warn('Please change the default password after first login!');
    }
}
