<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserController extends Controller
{

    public function getUser()
    {
        $usersWithRoles = User::with('roles')->get();
    
        $formattedUsers = $usersWithRoles->map(function ($user) {
            return [
                'user' => $user,
                'roles' => $user->roles->pluck('name'),
            ];
        });
    
        return response()->json([
            'users_with_roles' => $formattedUsers,
        ], 200);
    }
    

    public function insertUser(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'user_email' => 'required|email|unique:users,email',
                'user_password' => 'required|min:6',
                'user_name' => 'required|max:50',
                'user_status' => 'required|numeric',
                'user_phone' => 'required|numeric|digits:10',
                // 'user_role_id' => 'required|exists:roles,id'
            ], [
                'user_email.required' => 'email không được trống',
                'user_email.email' => 'email phải để đúng định dạng email',
                'user_email.unique' => 'email đã tồn tại',
                'user_password.required' => 'password không được để trống',
                'user_password.min' => 'password phải ít nhất 6 kí tự',
                'user_name.required' => 'name không được để trống',
                'user_name.max' => 'name chỉ được dưới 50 kí tự',
                'user_status.required' => 'status không được để trống',
                'user_status.numeric' => 'status phải là số',
                'user_phone.required' => 'phone không để trống',
                'user_phone.numeric' => 'phone phải là số',
                'user_phone.digits' => 'phone phải có đúng 10 số',
                // 'user_role_id.required' => 'vai trò người dùng không được trống',
                // 'user_role_id.exists' => 'vai trò người dùng không tồn tại'
            ]);
            

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            

            User::create([
                'email' => $request->user_email,
                'password' => $request->user_password,
                'name' => $request->user_name,
                'status' => $request->user_status,
                'phone' => $request->user_phone,
                // 'roles_id' => $request->user_role_id,
            ]);
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $user = User::find($id);
            if ($user == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy user'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'user_name' => 'required',
                'user_status' => 'required|numeric',
                'user_phone' => 'required|numeric|digits:10',
                // 'user_role_id' => 'required|exists:roles,id'
            ], [
                'user_name.name' => 'name không được để trống',
                'user_status.required' => 'status không được để trống',
                'user_status.numeric' => 'status phải là số',
                'user_phone.required' => 'phone không để trống',
                'user_phone.numeric' => 'phone phải là số',
                'user_phone.digits' => 'phone phải có đúng 10 số',
                // 'user_role_id.required' => 'vai trò người dùng không được trống',
                // 'user_role_id.exists' => 'vai trò người dùng không tồn tại'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }

            $user->name = $request->user_name;
            $user->status = $request->user_status;
            $user->phone = $request->user_phone;
            $user->save();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $user = User::find($id);
            if ($user == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy user'
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
