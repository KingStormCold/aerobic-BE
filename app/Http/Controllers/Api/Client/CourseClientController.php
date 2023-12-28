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
        $subjectName ="";
        $subjectImage ="";
        // $subjectContent = "";
        $courseArray = [];

        foreach ($courses as $course) {
            if ($subjectId != $course->subject_id) {
                if ($subjectId != null) {
                    $data = [
                        "subject_id" => $subjectId,
                        "subjectName" => $subjectName,
                        "subjectImage" => $subjectImage,
                        // "subjectContent" => $subjectContent,
                        "courses" =>  $courseArray
                    ];
                    $result = $data;
                    $courseArray = [];
                }
                $subjectId = $course->subject_id;
                $subject = Subject::find($subjectId);
                if ($subject) {
                    $subjectName = $subject->name;
                    $subjectImage = $subject->image;
                    $subjectContent = $subject->content;
                }
            }
            $subjectData = [
                "course_id" => $course->id,
                "courseName" => $course->name,
                "course_description" => $course->description,
                "level" => $course->level,
            ];
            array_push($courseArray, $subjectData);
        }

        // Thêm dữ liệu cuối cùng
        if ($subjectId != null) {
            $data = [
                "subject_id" => $subjectId,
                "subjectName" => $subjectName,
                "subjectImage" =>  $subjectImage,
                // "subjectContent" => $subjectContent,
                "course" => $courseArray
            ];
            $result = $data;
        }

        return $result;
    }
}
