<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use App\Models\Course;
use App\Models\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SearchClientController extends Controller
{
    public function searchClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content_search' => 'required|max:255',
        ], [
            'content_search.required' => 'Enter a search term',
            'content_search.max' => 'Type less than 255 characters'
        ]);
        try {
            $content_search = $request->input('content_search');
            $subjects = Subject::with('category')->select('id', 'content', 'name', 'image', 'promotional_price', 'category_id')->where("content", "like", "%" . $content_search . "%")->where('status', 1)->paginate(10);
            if ($subjects->isEmpty()) {
                return response()->json([
                    'message' => 'No subject found.'
                ], 400);
            }
            $results = [];
            foreach ($subjects as $subject) {
                $results[] = $this->customSearch($subject);
            }
            return response()->json([
                'results' => $results,
                'totalPage' => $subjects->lastPage(),
                'pageNum' => $subjects->currentPage()
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customSearch($subject)
    {
        $courses = $subject->courses;
        $totalCourseFee = 0;
        $totalDiscount = 0;
        $totalVideos = 0;
        foreach ($courses as $course) {
            $totalCourseFee += $course->price;
            $totalDiscount += $course->promotional_price;
            $totalVideos += $course->videos->count();
        }
        $totalDiscount += $subject->promotional_price;
        $categoryData = [
            "subject_id" => $subject->id,
            "subject_name" => $subject->name,
            "subject_image" => $subject->image,
            "total_course_fee" => $totalCourseFee,
            "total_discount" => $totalDiscount,
            "total_videos" => $totalVideos,
            "category_id" => $subject->category->id
        ];
        return $categoryData;
    }
}
