<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CategoryController extends Controller
{

    public function getParentCategories()
    {
        $categories = Category::where('parent_id', '=', "")->get();
        return response()->json([
            'categories' => $categories
        ], 200);
    }

    public function insertCategory(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_CATEGORY');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|unique:categories,name',
            ], [
                'category_name.required' => 'Tên danh mục không được trống',
                'category_name.unique' => 'Tên danh mục đã tồn tại',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            if ($request->parent_id != null) {
                $category = Category::find($request->parent_id);
                if ($category == null) {
                    return response()->json([
                        'error_message' => 'Danh mục cha không đúng'
                    ], 400);
                }
            }

            Category::create([
                'name' => $request->category_name,
                'parent_id' => $request->parent_id == null ? '' : $request->parent_id
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

    public function updateCategory($id, Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_CATEGORY');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $category = Category::find($id);
            if ($category == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy category'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|unique:categories,name,' . $category->id,
            ], [
                'category_name.required' => 'Tên danh mục không được trống',
                'category_name.unique' => 'Tên danh mục đã tồn tại',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }

            if ($request->parent_id != null) {
                $categoryParent = Category::find($request->parent_id);
                if ($categoryParent == null) {
                    return response()->json([
                        'error_message' => 'Danh mục cha không đúng'
                    ], 400);
                }
            }

            $category->name = $request->category_name;
            $category->parent_id = $request->parent_id == null ? '' : $request->parent_id;
            $category->save();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = Category::find($id);
            if ($category == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy category'
                ], 400);
            }
            $category->delete();
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
