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
                ], 404);
            }
            

            return response()->json([
                'courses' => $this->customfullCourses($courses),

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
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
