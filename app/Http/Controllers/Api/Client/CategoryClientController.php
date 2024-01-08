<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use CloudCreativity\LaravelJsonApi\Pagination\CursorStrategy;
use Throwable;

use function PHPUnit\Framework\isEmpty;

class CategoryClientController extends Controller
{
    public function getMenu()
    {
        $categories = Category::where('parent_id', 0)->where('status', 1)->get();
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
                $categoryData['sub_menu'] = $this->buildMenu($subCategories);
            }

            $result[] = $categoryData;
        }
        return $result;
    }
    public function getSubCategories($parentId)
    {
        return Category::where('parent_id', $parentId)->where('status', 1)->get();
    }
}
