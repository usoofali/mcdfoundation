<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;

class RoleService
{
    public function createRole(string $name, ?string $description = null): Role
    {
        return Role::create([
            'name' => $name,
            'description' => $description,
        ]);
    }

    public function assignPermissions(Role $role, array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $role->permissions()->sync($permissions->pluck('id'));
    }

    public function revokeAllPermissions(Role $role): void
    {
        $role->permissions()->detach();
    }

    public function getRolePermissions(Role $role): array
    {
        return $role->permissions()->pluck('name')->toArray();
    }

    public function createPermission(string $name, string $module, ?string $description = null): Permission
    {
        return Permission::create([
            'name' => $name,
            'module' => $module,
            'description' => $description,
        ]);
    }

    public function getPermissionsByModule(string $module): array
    {
        return Permission::where('module', $module)->pluck('name')->toArray();
    }
}
