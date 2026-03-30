<?php

namespace App\Traits;

trait HasRolePermission
{
    protected $permissionMap = [
        'View'   => ['index'],
        'Create' => ['create', 'store'],
        'Edit'   => ['edit', 'update'],
        'Delete' => ['destroy'],
        'Import' => ['import'],
        'Export' => ['export'],
    ];
    public function applyRolePermissions($baseName)
    {
        $controller = $this;
        $permissions = \App\Models\Permission::where('name', 'like', $baseName . '%')->get();
        foreach ($permissions as $permission) {
            $action = last(explode(' ', $permission->name));
            $methods = $this->permissionMap[$action] ?? [];
            if (!empty($methods)) {
                $controller->middleware('permission:' . $permission->name, ['only' => $methods]);
            }
        }
    }
    public static function userCan(...$permissionNames)
    {
        $userPermissions = session('MKT_USER.permissions', []);
        foreach ($permissionNames as $permissionName) {
            if (in_array($permissionName, $userPermissions)) {
                return true; // has at least one permission
            }
        }
        return false; // has none
    }

    public function denyPermission($permissionName)
    {
        if (!auth()->check()) {
            return false;
        }
        return auth()->user()->can($permissionName);
    }
}