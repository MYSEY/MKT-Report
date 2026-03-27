<?php

namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            // get permissions for user's role
            $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_has_permissions.role_id', $user->Role)
                ->pluck('permissions.name')
                ->toArray();
    
            return in_array($ability, $permissions) ? true : null;
        });
    }
}
