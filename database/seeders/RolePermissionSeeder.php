<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=RolePermissionSeeder
     */
    public function run(): void
    {
        $categories = [
            'Role',
            'Permission',
            'Category',
            'User',
            'Loan Detail',
            'CO Performance',
        ];
    
        $actions = ['View', 'Create', 'Edit', 'Delete', 'Import', 'Export'];
        $permissionIds = [];
        foreach ($categories as $catName) {
            $categoryId = DB::table('categories')->updateOrInsert(
                ['name' => $catName],
                [
                    'name' => $catName,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $categoryId = DB::table('categories')->where('name', $catName)->value('id');
            foreach ($actions as $action) {
                $permissionName = $catName . ' ' . $action;
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permissionName],
                    [
                        'category_id' => $categoryId,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                $permissionIds[] = $permissionId;
            }
        }
    
        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => 99,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
