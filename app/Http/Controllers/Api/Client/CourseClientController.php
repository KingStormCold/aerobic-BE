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
    public function fullCourses()
    {
        try {
            $courses = Course::orderByDesc('created_at')->paginate(10);
            return response()->json([
                'courses' => $this->customfullCourses($courses->items()),

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customfullCourses($courses)
    {
        $result = [];
        foreach ($courses as $course) {
            $subjectName = "";
            $subject = Subject::find($course->subject_id);
            if ($subject) {
                
                $subjectName= $subject->name;
            }
            $data = [
                "subject_id" => $course->subject_id,
                "subject_name" => $subjectName,
                "id_course" => $course->id,
                "name" => $course->name,
                "description" => $course->description,               
                "level" => $course->level, 
                "price" => $course->price,
                "promotional_price" => $course->promotional_price,
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
