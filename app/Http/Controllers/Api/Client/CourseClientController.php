<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\Validator;

class CourseClientController extends Controller
{
    public function fullCourses($subjectId)
    {
        try {
            $courses = Course::where('subject_id',$subjectId)->orderByDesc('level')->get();
            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json([
                    'message' => 'Không tìm thấy môn học.'
                ], 400);
            }
            return response()->json(
               $this->customfullCourses($courses),

             200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customfullCourses($courses)
    {
        $result = [];
        $subjectId = null;
        $subject_name ="";
        $subject_image ="";
        $courseArray = [];
        $price = 0;
        $promotionalPrice = 0;
        foreach ($courses as $course) {
            
            $price += $course->price;
            $promotionalPrice += $course->promotional_price;
            $subjectFull = [
                "course_id" => $course->id,
                "course_name" => $course->name,
                "course_description" => $course->description,
                "level" => $course->level,
                "price"  => $course->price,
                "promotional_price" => $course->promotional_price,

            ];
            array_push($courseArray, $subjectFull);
        }
        if(empty($courseArray)){
            return [];
        }

        $subjectFull = [
            "course_id" => 0,
            "course_name" =>'Toàn bộ khóa học',
            "course_description" => '',
            "level" => '',
            "price"  => $price,
            "promotional_price" => $promotionalPrice,

        ];
        array_push($courseArray, $subjectFull);

        return $courseArray;
    }
}
