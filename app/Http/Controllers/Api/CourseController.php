<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function getCourse()
    {
        $courses = Course::where('subject_id', '>', "0")->get();
        return response()->json([
            'courses' => $courses
        ], 200);
    }

    public function courses($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_COURSE');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $courses = Course::where('subject_id', $id)->where('status', 1)->paginate(10);
            return response()->json([
                'courses' => $this->customCourses($courses->items()),
                'totalPage' => $courses->lastPage(),
                'pageNum' => $courses->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customCourses($courses)
    {
        $result = [];
        foreach ($courses as $course) {
            $subjectName = "";
            if ($course->subject_id !== "") {
                $subject_name = Subject::find($course->subject_id);
                $subjectName = $subject_name->name;
            }
            $data = [
                "id" => $course->id,
                "name" => $course->name,
                "subject_id" => $course->subject_id,
                "subject_name" => $subjectName,
                "description" => $course->description,
                "level" => $course->level,
                "price" => $course->price,
                "promotional_price" => $course->promotional_price,
                "created_by" => $course->created_by,
                "updated_by" => $course->updated_by,
                "created_at" => $course->created_at,
                "updated_at" => $course->updated_at,
            ];
            array_push($result, $data);
        }
        return $result;
    }

    public function showCourse($id)
    {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_COURSE');
        if (!$isAuthorization) {
            return response()->json([
                'code' => 'CATE_001',
                'message' => 'You have no rights.'
            ], 401);
        }
        $courses = Course::find($id);
        if ($courses == null) {
            return response()->json([
                'error_message' => 'Science not found'
            ], 400);
        }
        return response()->json([
            'courses' => $courses
        ], 200);
    }

    public function insertCourse(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_COURSE');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'description' => 'required|string|max:255',
                'level' => [
                    'required',
                    'integer',
                ],
                'price' => 'required|numeric',
                'subject_id' => 'required|exists:subjects,id',
            ], [
                'name.required' => 'Course name cant be blank',
                'name.max' => 'The course name should not exceed 100 characters',
                'description.required' => 'Course descriptions cannot be left blank',
                'description.max' => 'The course description should not exceed 255 characters',
                'level.required' => 'Course levels must not be empty',
                'level.integer' => 'The course level must be an integer',
                'price.required' => 'Course prices cant be blank',
                'price.numeric' => 'The course price should be several',
                'subject_id.required' => 'Subject IDs cant be blank',
                'subject_id.exists' => 'Subject ID does not exist in the subject list',
                //'promotional_price.required' => 'Cấp độ khóa học không được trống',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            Course::create([
                'name' => $request->name,
                'description' => $request->description,
                'level' => $request->level,
                'price' => $request->price,
                'subject_id' => $request->subject_id,
                'promotional_price' => $request->promotional_price,
                'created_by' => $authController->getEmail()
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function updateCourse(Request $request, $id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_COURSE');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $course = Course::find($id);
            if (!$course) {
                return response()->json([
                    'error_message' => 'Course not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'description' => 'required|string|max:500',
                'level' => [
                    'required',
                    'integer',
                ],
                'price' => 'required|numeric',
                'subject_id' => 'required|exists:subjects,id',
                ''
            ], [
                'name.required' => 'Course name cant be blank',
                'name.max' => 'The course name should not exceed 100 characters',
                'description.required' => 'Course descriptions cannot be left blank',
                'description.max' => 'The course description should not exceed 255 characters',
                'level.required' => 'Course levels must not be empty',
                'level.integer' => 'The course level must be an integer',
                'price.required' => 'Course prices cant be blank',
                'price.numeric' => 'The course price should be several',
                'subject_id.required' => 'Subject IDs cant be blank',
                'subject_id.exists' => 'Subject ID does not exist in the subject list',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            $course->update([
                'name' => $request->name,
                'description' => $request->description,
                'level' => $request->level,
                'price' => $request->price,
                'promotional_price' => $request->promotional_price,
                'subject_id' => $request->subject_id,
                'updated_by' => $authController->getEmail()
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function deleteCourse($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_COURSE');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $course = Course::find($id);
            if (!$course) {
                return response()->json([
                    'error_message' => 'Course not found'
                ], 404);
            }
            $course->update([
                'status' => 0
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function showCourseName()
    {
        $result = [];
        $courses = Course::get();
        foreach ($courses as $course) {
            $data = [
                "id" => $course->id,
                "name" => $course->name,
            ];
            array_push($result, $data);
        }
        return response()->json([
            'courses' => $result
        ], 200);
    }
}
