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
                'message' => 'Bạn không có quyền.'
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
                    'message' => 'Bạn không có quyền.'
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
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                'message' => 'Bạn không có quyền.'
            ], 401);
        }
        $user = User::find($id);
        if ($user == null) {
            return response()->json([
                'error_message' => 'Không tìm thấy user'
            ], 400);
        }
        return response()->json([
            'user' => $user
        ], 200);
    }




    public function insertUser(Request $request)
    {
        // try {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_USER');
        if (!$isAuthorization) {
            return response()->json([
                'message' => 'Bạn không có quyền.'
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
            'user_email.required' => 'Email không được trống',
            'user_email.email' => 'Email phải đúng định dạng',
            'user_email.unique' => 'Email đã tồn tại',
            'user_password.required' => 'Password không được để trống',
            'user_password.min' => 'Password phải ít nhất 6 kí tự',
            'user_password.confirmed' => 'xác nhận lại Password ko đúng',
            'user_fullname.required' => 'Họ và tên không được để trống',
            'user_fullname.max' => 'Họ và tên không được vượt quá 100 kí tự',
            'user_role_name.required' => 'Vai trò người dùng không được trống',
            'user_role_name.array' => 'Vai trò người dùng phải là một mảng',
            'user_role_name.*.exists' => 'Vai trò người dùng không tồn tại',
            'user_phone.required' => 'Số điện thoại không được để trống',
            'user_phone.numeric' => 'Số điện thoại phải là số',
            'user_phone.digits' => 'Số điện thoại phải có đúng 10 số',
            'user_status.required' => 'Trạng thái không được để trống',
            'user_status.numeric' => 'Trạng thái phải là số',
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

        // Thêm quyền cho người dùng thông qua bảng trung gian
        $user->roles()->attach($request->user_role_name);

        return response()->json([
            'result' => 'success',
        ], 200);
        // } catch (Exception $e) {
        //     return response()->json([
        //         'error_message' => 'lỗi hệ thống'
        //     ], 500);
        // }
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
                'user_fullname' => 'required|max:100,' . $user->id,
                'user_role_name' => 'required|array',
                'user_role_name.*' => 'exists:roles,name',
                'user_phone' => 'required|numeric|digits:10',
                'user_money' => 'numeric',
                'user_status' => 'required|numeric',
            ], [
                'user_fullname.required' => 'Họ và tên không được để trống',
                'user_fullname.max' => 'Họ và tên không được vượt quá 100 kí tự',
                'user_role_name.required' => 'Vai trò người dùng không được trống',
                'user_role_name.array' => 'Vai trò người dùng phải là một mảng',
                'user_role_name.*.exists' => 'Vai trò người dùng không tồn tại',
                'user_phone.required' => 'Số điện thoại không được để trống',
                'user_phone.numeric' => 'Số điện thoại phải là số',
                'user_phone.digits' => 'Số điện thoại phải có đúng 10 số',
                'user_money.numeric' => 'Số tiền phải là số',
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
                        'error_message' => 'Danh mục cha không đúng'
                    ], 400);
                }
            }
            // Cập nhật thông tin trong bảng users
            $user->money = $request->input('user_money');
            $user->status = $request->input('user_status');
            $user->phone = $request->input('user_phone');

            // Kiểm tra nếu giá trị user_updated_by không phải là null
            $user->updated_by = auth()->user()->email;

            $user->save();

            // Cập nhật vai trò trong bảng users_roles
            $user->roles()->sync($request->input('user_role_name'));

            return response()->json([
                'result' => 'success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'lỗi hệ thống'
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
