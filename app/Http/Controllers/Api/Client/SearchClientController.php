<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use App\Models\Course;
use App\Models\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchClientController extends Controller
{
    public function searchClient(Request $request) {
        $validator = Validator::make($request->all(), [
            'content_search' => 'required|max:255',
        ],[
            'content_search.required' => 'hãy nhập từ khóa tìm kiếm',
            'content_search.max' => 'chỉ nhập dưới 255 kí tự'
        ]);
    
        try {
            $content_search = $request->input('content_search');
            $subjects = Subject::select('id','content','name','image','promotional_price')->where("content","like","%".$content_search."%")->get();
            if ($subjects->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy môn học.'
                ], 400);
            }
            $results = [];
            foreach ($subjects as $subject) {
                $results[] = $this->customSearch($subject);
            }
            return response()->json([
                'results' => $results,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
            "subject_content" => $subject->content,
            "subject_image" => $subject->image,
            "total_course_fee" => $totalCourseFee,
            "total_discount" => $totalDiscount,
            "total_videos" => $totalVideos,
        ];
    
        return $categoryData;
    }
    
}
