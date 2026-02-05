<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Hash;

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
        $request->validate([
            'number_employee' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('number_employee', $request->number_employee)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Wrong employee ID or password',
                'status' => 'error'
            ]);
        }

        // Resigned user check
        if(in_array($user->emp_status, ['3','4','5','6','7','8','9'])){
            if ($user->resign_date && $user->resign_date <= now()->toDateString()) {
                return response()->json([
                    'message' => 'Your account is not active. Please contact support',
                    'status' => 'error'
                ]);
            }
        }

        // Role check
        if (empty($user->role_id)) {
            return response()->json([
                'message' => "You don't have permission to view this page",
                'status' => 'error'
            ]);
        }

        // Status check
        if ($user->status !== 'Active') {
            return response()->json([
                'message' => 'Your account is not active. Please contact support',
                'status' => 'error'
            ]);
        }

        // First-time password logic
        if ($user->p_status == 0) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Wrong employee ID or password',
                    'status' => 'error'
                ]);
            }

            return response()->json([
                'message' => 'Login successfully',
                'status' => 'success',
                'role' => null
            ]);
        }

        // Normal login
        if (!Auth::attempt($request->only('number_employee', 'password'))) {
            return response()->json([
                'message' => 'Wrong employee ID or password',
                'status' => 'error'
            ]);
        }
            
        if (Auth::attempt(['number_employee' => $request->number_employee, 'password' => $request->password])) {
            if (Auth::user()->status == 'Active') {
                Toastr::success('Login successfully.', 'Success');
                return redirect('admin/dashboad');
            } else {
                Auth::logout();
                Toastr::error('Your account is not active. Please contact support.', 'Error');
                return redirect('login');
            }
        }else {
            Toastr::error('Wrong Email Or Password', 'Error');
            return redirect('login');
        }
    }
    public function logout()
    {
        Auth::logout();
        Toastr::success('Logout successfully', 'Success');
        return redirect('login');
    }
}
