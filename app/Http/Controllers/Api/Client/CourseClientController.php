<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CourseClientController extends Controller
{
    public function fullCourses($subjectId)
    {
        try {
            $courses = Course::where('subject_id', $subjectId)->where('status', 1)->orderBy('level')->get();
            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json([
                    'message' => 'No subject found.'
                ], 400);
            }
            return response()->json(
                $this->customfullCourses($courses, $subject),
                200
            );
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }
    public function customfullCourses($courses, $subject)
    {
        $courseArray = [];
        $price = 0;
        $promotionalPrice = 0;
        foreach ($courses as $course) {
            $price += $course->price;
            $promotionalPrice += $course->promotional_price;
            if ($course->level === 1) {
                $subjectFull = [
                    "course_id" => $course->id,
                    "course_name" =>   $course->name . '( Học Thử )',
                    "course_description" => $course->description,
                    "level" => $course->level,
                    "price"  => 0,
                    "promotional_price" => 0,
                ];
                array_push($courseArray, $subjectFull);
            }
            $subjectFull = [
                "course_id" => $course->id,
                "course_name" =>  $course->name,
                "course_description" => $course->description,
                "level" => $course->level,
                "price"  => $course->price,
                "promotional_price" => $course->promotional_price,
            ];
            array_push($courseArray, $subjectFull);
        }
        if (empty($courseArray)) {
            return [];
        }
        $subjectFull = [
            "course_id" => 0,
            "course_name" => 'Toàn bộ khóa học',
            "course_description" => '',
            "level" => '',
            "price"  => $price,
            "promotional_price" => $promotionalPrice + $subject->promotional_price,
        ];
        array_push($courseArray, $subjectFull);

        return $courseArray;
    }
}
