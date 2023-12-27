<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use CloudCreativity\LaravelJsonApi\Pagination\CursorStrategy;
use Throwable;

use function PHPUnit\Framework\isEmpty;

class CategoryController extends Controller
{

    public function getParentCategories()
    {
        $categories = Category::where('parent_id', '=', "")->get();
        return response()->json([
            'categories' => $categories
        ], 200);
    }

    public function getCategories()
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
            $categories = Category::orderByDesc('parent_id')->paginate(10);
            return response()->json([
                'categories' => $this->customCategories($categories->items()),
                'totalPage' => $categories->lastPage(),
                'pageNum' => $categories->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function customCategories($categories)
    {
        $result = [];
        foreach ($categories as $category) {
            $parentName = "";
            if ($category->parent_id !== "") {
                $parentCategory = Category::find($category->parent_id);
                $parentName = $parentCategory->name;
            }
            $data = [
                "id" => $category->id,
                "name" => $category->name,
                "parent_id" => $category->parent_id,
                "parent_name" => $parentName,
                "created_by" => $category->created_by,
                "updated_by" => $category->updated_by,
                "created_at" => $category->created_at,
                "updated_at" => $category->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }

    public function getCategory($id)
    {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_CATEGORY');
        if (!$isAuthorization) {
            return response()->json([
                'code' => 'CATE_001',
                'message' => 'Bạn không có quyền.'
            ], 401);
        }
        $category = Category::find($id);
        if ($category == null) {
            return response()->json([
                'error_message' => 'Không tìm thấy category'
            ], 400);
        }
        return response()->json([
            'category' => $category
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
                'parent_id' => $request->parent_id == null ? '' : $request->parent_id,
                'created_by' => $authController->getEmail(),
                'updated_by' => $authController->getEmail()
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
            $category->updated_by = $authController->getEmail();
            $category->save();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::debug($e);
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

    /**Lấy danh sách các môn học dựa trên một điều kiện về danh mục */
    public function getChildCategories()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_CATEGORY');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $categories = Category::with('subjects')->where('parent_id', '>', "0")->get();
            $result = [];
            foreach ($categories as $category) {
                if ($category->subjects->count() === 0) {
                    $data = [
                        "id" => $category->id,
                        "name" => $category->name,
                        "parent_id" => $category->parent_id,
                    ];
                    array_push($result, $data);
                }
            }
            return response()->json([
                'categories' => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function getMenu()
    {
        $categories = Category::where('parent_id', 0)->get();
        $menu = $this->buildMenu($categories);
        return response()->json([
            'menu' => $menu,
        ], 200);
    }
    public function buildMenu($categories)
    {
        $result = [];
        
        foreach ($categories as $category) {
            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
            ];
            $subCategories = $this->getSubCategories($category->id);
            if (!$subCategories->isEmpty()) {
                $categoryData['sub-menu'] = $this->buildMenu($subCategories);
            }
        
            $result[] = $categoryData;
        }
        return $result;
    }
    public function getSubCategories($parentId)
 {
      return Category::where('parent_id', $parentId)->get();
 }
}
