<?php

namespace App\Http\Middleware;

use Closure;

class MktAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (!session()->has('MKT_USER')) {
            return redirect('admin/login');
        }

        return $next($request);
    }
}
