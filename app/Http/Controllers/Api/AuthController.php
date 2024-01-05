<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Incorrect account or password'], 401);
        }
        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_fullname' => 'required|max:100',
                'user_email' => 'required|email|unique:users,email',
                'user_password' => 'required|min:6',
                'user_phone' => 'required|numeric|digits:10',
            ], [
                'user_fullname.required' => 'Full name must not be blank',
                'user_fullname.max' => 'Full name must not exceed 100 characters',
                'user_email.required' => 'Email cant be blank',
                'user_email.email' => 'Emails must be in the correct format',
                'user_email.unique' => 'Email already exists',
                'user_password.required' => 'Password must not be blank',
                'user_password.min' => 'Password must be at least 6 characters',
                'user_phone.required' => 'Phone numbers cant be blank',
                'user_phone.numeric' => 'The phone number must be a number',
                'user_phone.digits' => 'The phone number must have exactly 10 numbers',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            $user = User::create([
                'email' => $request->user_email,
                'password' => bcrypt($request->user_password),
                'fullname' => $request->user_fullname,
                'status' => 1,
                'phone' => $request->user_phone,
                'money' => 0,
                'uuid' => ''
            ]);
            $defaultRole = Role::where('name', 'USER')->first();
            $user->roles()->attach($defaultRole);
            return response()->json([
                'message' => 'Successful user registration',
                'user' => $user,
                'vai trò' => $defaultRole->name,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System errors'
            ], 500);
        }
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        $user = auth()->user();
        $currentUser = User::with('roles')->findOrFail($user->id);
        $roleList = UserRole::where('users_id', $user->id)->get();
        $roles = $this->mapRole($roleList);
        return response()->json([
            'data' => [
                'id' => $currentUser->id,
                'fullname' => $currentUser->fullname,
                'email' => $currentUser->email,
                'roles' => $roles
            ],
        ], 200);
    }

    public function getRoles()
    {
        $user = auth()->user();
        if ($user) {
            $roleList = UserRole::where('users_id', $user->id)->get();
            return $this->mapRole($roleList);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function mapRole($roles)
    {
        $result = [];
        foreach ($roles as $role) {
            array_push($result, $role->roles_name);
        };
        return $result;
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            // 'user' => auth()->user()
        ]);
    }

    public function changePassWord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = auth()->user()->id;
        $old_password = $request->input('old_password');
        $user = User::find($userId);
        if ($user && password_verify($old_password, $user->password)) {
            $user->update([
                'password' => bcrypt($request->new_password)
            ]);
            return response()->json([
                'message' => 'You have successfully changed your password',
            ], 201);
        } else {
            return response()->json([
                'error_message' => 'Old password doesnt match',
            ], 400);
        }
    }

    public function isAuthorization($roleName): bool
    {
        $roles = $this->getRoles();
        $result = false;
        foreach ($roles as $role) {
            if ($role == $roleName) {
                $result = true;
            }
        }
        return $result;
    }
    public function getEmail(): string
    {
        $user = auth()->user();
        $currentUser = User::find($user->id);
        return $currentUser->email;
    }

    public function forgotPass(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $uuid = Str::uuid();
        $user->uuid = $uuid;
        $user->save();
        // Send email with the UUID link here
        return response()->json(['message' => 'Reset password link sent']);
    }

    public function postForgotPass(Request $request)
    {
        // This method is not needed if you're using UUIDs
    }

    public function getPass(Request $request)
    {
        $request->validate(['uuid' => 'required']);
        $user = User::where('uuid', $request->uuid)->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid UUID'], 400);
        }
        $user->uuid = '';
        $user->save();
        return response()->json(['message' => 'UUID is valid']);
    }

    public function postGetPass(Request $request)
    {
        $request->validate([
            'uuid' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::where('uuid', $request->uuid)->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid UUID'], 400);
        }
        $user->password = bcrypt($request->password);
        $user->uuid = '';
        $user->save();
        return response()->json(['message' => 'Password has been reset']);
    }
}
