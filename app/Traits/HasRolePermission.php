<?php

namespace App\Traits;

use App\Models\Permission;

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
        // $controller = $this;
        // $permissions = \App\Models\Permission::where('name', 'like', $baseName . '%')->get();
        // foreach ($permissions as $value) {
        //     $action = last(explode(' ', $value->name));
        //     $methods = $this->permissionMap[$action] ?? [];
        //     if (!empty($methods)) {
        //         $controller->middleware('permission:' . $value->name, ['only' => $methods]);
        //     }
        // }
        
        $permissions = Permission::where('name', 'like', $baseName . '%')->get();
        foreach ($permissions as $value) {
            $action = last(explode(' ', $value->name));
            $methods = $this->permissionMap[$action] ?? [];
            if (!empty($methods)) {
                foreach ($methods as $method) {
                    if (request()->routeIs($method)) {
                        if (!auth()->user()->can($value->name)) {
                            abort(403, "Your role can't access permission");
                        }
                    }
                }
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