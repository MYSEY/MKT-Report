<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\HRConnection;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function index(){
        return view('auth.login');
    }

    public function login(Request $request)
    {
        try {
            //User MKT
            $user = DB::connection('pgsql')
            ->table('MKT_USER')
            ->leftJoin('MKT_ROLE', 'MKT_USER.Role', '=', 'MKT_ROLE.ID')
            ->select(
                'MKT_USER.Role',
                'MKT_USER.Password',
                'MKT_USER.Active',
                'MKT_USER.LogInName',
                'MKT_USER.DisplayName',
                'MKT_ROLE.Description as RoleName'
            )->where('Active', 'Yes')->whereRaw('"MKT_USER"."LogInName" = ?', [$request->number_employee])->first();
            if ($user) {
                if (!$user || !Helper::verifyPbkdf2(trim($request->password), trim($user->Password))) {
                    return response()->json([
                        'message' => 'Username/Password is incorrect',
                        'status'  => 'error'
                    ]);
                }
               $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_has_permissions.role_id', $user->Role)
                ->pluck('permissions.name')
                ->toArray();

                session()->put('MKT_USER', [
                    'role'          => $user->Role,
                    'name'        => $user->LogInName,
                    'displayName' => $user->DisplayName,
                    'roleName'    => $user->RoleName,
                    'permissions' => $permissions
                ]);
                
                return response()->json([
                    'message' => 'Login successfully',
                    'status' => 'success',
                ]); 
            }else{
                return response()->json([
                    'message'=> 'Your account is not active',
                    'status'=> 'error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Login failed. Please try again',
                'status' => 'error'
            ], 500);
        }
    }
    public function logout()
    {
        Auth::logout();
        Toastr::success('Logout successfully', 'Success');
        return redirect('login');
    }
}
