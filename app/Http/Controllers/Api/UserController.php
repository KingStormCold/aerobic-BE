<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserController extends Controller
{
    public function getRoles()
    {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_USER');
        if (!$isAuthorization) {
            return response()->json([
                'message' => 'You have no rights.'
            ], 401);
        }
        $result = [];
        $roles = Role::get();
        foreach ($roles as $role) {
            $data = [
                "name" => $role->name
            ];
            array_push($result, $data);
        }
        return response()->json([
            'roles' => $result,
        ], 200);
    }

    public function getUsers()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'You have no rights.'
                ], 401);
            }
            $users = User::orderByDesc('created_at')->paginate(10);
            return response()->json([
                'users' => $this->customUsers($users->items()),
                'totalPage' => $users->lastPage(),
                'pageNum' => $users->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customUsers($users)
    {
        $result = [];
        foreach ($users as $user) {
            $roles = UserRole::where('users_id', $user->id)->get();
            $roleList = [];
            foreach ($roles as $role) {
                array_push($roleList, $role->roles_name);
            }
            $data = [
                "id" => $user->id,
                "email" => $user->email,
                "fullname" => $user->fullname,
                "status" => $user->status,
                "phone" => $user->phone,
                "roles" => $roleList,
                "created_by" => $user->created_by,
                "updated_by" => $user->updated_by,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }

    public function getUser($id)
    {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_USER');
        if (!$isAuthorization) {
            return response()->json([
                'message' => 'You have no rights.'
            ], 401);
        }
        $user = User::find($id);
        if ($user == null) {
            return response()->json([
                'error_message' => 'No user found'
            ], 400);
        }
        return response()->json([
            'user' => $user
        ], 200);
    }

    public function insertUser(Request $request)
    {
        try {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_USER');
        if (!$isAuthorization) {
            return response()->json([
                'message' => 'You have no rights.'
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|min:6|confirmed',
            'user_fullname' => 'required|max:100',
            'user_role_name' => 'required|array',
            'user_role_name.*' => 'exists:roles,name',
            'user_phone' => 'required|numeric|digits:10',
            'user_status' => 'required|numeric',
        ], [
            'user_email.required' => 'Email cant be blank',
            'user_email.email' => 'Emails must be in the correct format',
            'user_email.unique' => 'Email already exists',
            'user_password.required' => 'Password must not be blank',
            'user_password.min' => 'Password must be at least 6 characters',
            'user_password.confirmed' => 'reconfirm that the password is incorrect',
            'user_fullname.required' => 'Full name must not be blank',
            'user_fullname.max' => 'Full name must not exceed 100 characters',
            'user_role_name.required' => 'User roles cant be empty',
            'user_role_name.array' => 'User roles must be an array',
            'user_role_name.*.exists' => 'User role does not exist',
            'user_phone.required' => 'Phone numbers cant be blank',
            'user_phone.numeric' => 'The phone number must be a number',
            'user_phone.digits' => 'The phone number must have exactly 10 numbers',
            'user_status.required' => 'The status cannot be left blank',
            'user_status.numeric' => 'The status must be numeric',
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
            'status' => $request->user_status,
            'phone' => $request->user_phone,
            'money' => 0,
            'uuid' => '',
            'created_by' => auth()->user()->email,
        ]);
        $user->roles()->attach($request->user_role_name);
        return response()->json([
            'result' => 'success',
        ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System errors'
            ], 500);
        }
    }

    public function updateUser($id, Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'You have no rights.'
                ], 401);
            }
            $user = User::find($id);
            if ($user == null) {
                return response()->json([
                    'error_message' => 'No user found'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'user_fullname' => 'required|max:100,' . $user->id,
                'user_role_name' => 'required|array',
                'user_role_name.*' => 'exists:roles,name',
                'user_phone' => 'required|numeric|digits:10',
                'user_money' => 'numeric',
                'user_status' => 'required|numeric',
            ], [
                'user_fullname.required' => 'Full name must not be blank',
                'user_fullname.max' => 'Full name must not exceed 100 characters',
                'user_role_name.required' => 'User roles cant be empty',
                'user_role_name.array' => 'User roles must be an array',
                'user_role_name.*.exists' => 'User role does not exist',
                'user_phone.required' => 'Phone numbers cant be blank',
                'user_phone.numeric' => 'The phone number must be a number',
                'user_phone.digits' => 'The phone number must have exactly 10 numbers',
                'user_money.numeric' => 'The amount must be numeric',
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            if ($request->roles_name != null) {
                $userParent = User::find($request->roles_name);
                if ($userParent == null) {
                    return response()->json([
                        'error_message' => 'Incorrect parent category'
                    ], 400);
                }
            }
            $user->money = $request->input('user_money');
            $user->status = $request->input('user_status');
            $user->phone = $request->input('user_phone');
            $user->updated_by = auth()->user()->email;
            $user->save();
            $user->roles()->sync($request->input('user_role_name'));
            return response()->json([
                'result' => 'success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System errors'
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'You have no rights.'
                ], 401);
            }
            $user = User::find($id);
            if ($user == null) {
                return response()->json([
                    'error_message' => 'No user found'
                ], 400);
            }
            $user->delete();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }
}
