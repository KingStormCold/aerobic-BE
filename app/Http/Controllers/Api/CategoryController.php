<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
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
        $categories = Category::where('parent_id', '=', "")->where('status', 1)->get();
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
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $categories = Category::orderByDesc('parent_id')->paginate(10);
            return response()->json([
                'categories' => $this->customCategories($categories->items()),
                'totalPage' => $categories->lastPage(),
                'pageNum' => $categories->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                "status" => $category->status,
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
                'error_message' => 'You have no rights.'
            ], 401);
        }
        $category = Category::find($id);
        if ($category == null) {
            return response()->json([
                'error_message' => 'Category not found'
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
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|unique:categories,name',
            ], [
                'category_name.required' => 'Category name cant be blank',
                'category_name.unique' => 'The category name already exists',
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
                        'error_message' => 'Incorrect parent category'
                    ], 400);
                }
            }
            Category::create([
                'name' => $request->category_name,
                'parent_id' => $request->parent_id == null ? '' : $request->parent_id,
                'created_by' => $authController->getEmail(),
                'updated_by' => $authController->getEmail(),
                'status' => $request->status
            ]);
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $category = Category::find($id);
            if ($category == null) {
                return response()->json([
                    'error_message' => 'Category not found'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|unique:categories,name,' . $category->id,
            ], [
                'category_name.required' => 'Category name cant be blank',
                'category_name.unique' => 'The category name already exists',
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
                $checkCategoryParent = Category::where('parent_id', $id)->count();
                if ($checkCategoryParent !== 0) {
                    return response()->json([
                        'error_message' => 'Please delete all subcategories'
                    ], 400);
                }
                $categoryParent = Category::find($request->parent_id);
                if ($categoryParent == null) {
                    return response()->json([
                        'error_message' => 'Incorrect parent category'
                    ], 400);
                }
            }
            $category->name = $request->category_name;
            $category->parent_id = $request->parent_id === null ? '' : $request->parent_id;
            $category->updated_by = $authController->getEmail();
            $category->status = $request->status;
            $category->save();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
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
                    'error_message' => 'Category not found'
                ], 400);
            }
            $category->update([
                'status' => 0
            ]);

            $subject = Subject::where('category_id', $id);
            if ($subject !== null) {
                $subject->update([
                    'status' => 0
                ]);
            }
            if ($category->parent_id === "") {
                $categories = Category::where("parent_id", $id)->get();
                foreach ($categories as $item) {
                    $item->update([
                        'status' => 0
                    ]);
                }
            }

            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function getChildCategories()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_CATEGORY');
            if (!$isAuthorization) {
                return response()->json([
                    'error_message' => 'Bạn không có quyền.'
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
            Log::info('[Exception] ' + $e);
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
