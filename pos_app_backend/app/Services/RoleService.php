<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RoleService extends BaseService
{
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    public function createRole(array $data)
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'api',
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function getRoleById(int $id)
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function updateRole(int $id, array $data)
    {
        $role = Role::findOrFail($id);
        
        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function deleteRole(int $id)
    {
        return Role::destroy($id);
    }

    public function getAllPermissions()
    {
        return Permission::all()->groupBy('group');
    }

    // User management
    public function getAllUsers($perPage = 15)
    {
        return User::with('roles')->paginate($perPage);
    }

    public function createUser(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (isset($data['role_id'])) {
            $role = Role::findOrFail($data['role_id']);
            $user->assignRole($role->name);
        }

        return $user->load('roles');
    }

    public function getUserById(int $id)
    {
        return User::with('roles')->findOrFail($id);
    }

    public function updateUser(int $id, array $data)
    {
        $user = User::findOrFail($id);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (isset($data['role_id'])) {
            $role = Role::findOrFail($data['role_id']);
            $user->syncRoles([$role->name]);
        }

        return $user->load('roles');
    }

    public function deleteUser(int $id)
    {
        return User::destroy($id);
    }
}
