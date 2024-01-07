<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SubjectClientController extends Controller
{
    public function fullSubjects($categoryId)
    {
        try {
            $subject = Subject::where('category_id', $categoryId)->orderByDesc('created_at')->first();
            $category = Category::find($categoryId);
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found.'
                ], 400);
            }
            if (!$subject) {
                return response()->json([
                    'message' => 'No subjects found for this category.'
                ], 400);
            }
            return response()->json([
                'category' => $this->customfullSubject($subject),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customfullSubject($subject)
    {
        $categoryId = $subject->category_id;
        $category = Category::find($categoryId);
        $categoryData = [
            "subject_id" => $subject->id,
            "subject_name" => $subject->name,
            "subject_content" => $subject->content,
            "subject_image" => $subject->image,
        ];
        return $categoryData;
    }

    public function getFullSubjectClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content_search' => 'required|max:255',
        ], [
            'content_search.required' => 'Enter a search term',
            'content_search.max' => 'Type less than 255 characters',
        ]);
        $content_search = $request->input('content_search');
        try {
            $subjects = Subject::with('category')->select('id', 'name', 'created_at', 'image', 'promotional_price', 'category_id')->where("content", "like", "%" . $content_search . "%")->where('status', 1)->orderBy('created_at')->limit($request->input('page_size'))->get();
            $result = [];
            foreach ($subjects as $subject) {
                $subjectData = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'image' => $subject->image,
                    'promotional_price' => $subject->promotional_price,
                    'category_id' => $subject->category->id
                ];
                $result[] = $subjectData;
            }
            return response()->json([
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }
}
