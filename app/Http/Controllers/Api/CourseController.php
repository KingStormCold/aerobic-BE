<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use Exception;
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $courses = Course::where('subject_id', $id)->paginate(10);
            return response()->json([
                'courses' => $this->customCourses($courses->items()),
                'totalPage' => $courses->lastPage(),
                'pageNum' => $courses->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                'message' => 'Bạn không có quyền.'
            ], 401);
        }
        $courses = Course::find($id);
        if ($courses == null) {
            return response()->json([
                'error_message' => 'Không tìm thấy khoa học'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:courses,name',
                'description' => 'required|string|max:255',
                'level' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($request) {
                        $existingLevel = Course::where('subject_id', $request->subject_id)
                            ->where('level', $value)
                            ->first();

                        if ($existingLevel) {
                            $fail('Cấp độ đã tồn tại cho môn học này');
                        }
                    },
                ],
                'price' => 'required|numeric',
                'subject_id' => 'required|exists:subjects,id',
                //'promotional_price' => 'required',
            ], [
                'name.required' => 'Tên khóa học không được trống',
                'name.unique' => 'Tên khóa học đã tồn tại',
                'name.max' => 'Tên khóa học không được vượt quá 100 ký tự',
                'description.required' => 'Mô t trống',
                'description.max' => 'Mô tả khóa học không được vượt quá 255 ký tự',
                'level.required' => 'Cấp độ khóa học không được trống',
                'level.integer' => 'Cấp độ khóa học phải là một số nguyên',
                'level.unique' => 'Cấp độ khóa học đã tồn tại',
                'price.required' => 'Giá khóa học không được trống',
                'price.numeric' => 'Giá khóa học phải là một số',
                'subject_id.required' => 'ID môn học không được trống',
                'subject_id.exists' => 'ID môn học không tồn tại trong danh sách môn học',
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
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $course = Course::find($id);

            if (!$course) {
                return response()->json([
                    'error_message' => 'Không tìm thấy khóa học'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:courses,name,' . $course->id,
                'description' => 'required|string|max:500',
                'level' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($request, $course) {
                        if ($value != $course->level) {
                            $existingLevel = Course::where('subject_id', $request->subject_id)
                                ->where('level', $value)
                                ->first();

                            if ($existingLevel) {
                                $fail('Cấp độ đã tồn tại cho môn học này');
                            }
                        }
                    },
                ],
                'price' => 'required|numeric',
                'subject_id' => 'required|exists:subjects,id',
            ], [
                'name.required' => 'Tên khóa học không được trống',
                'name.unique' => 'Tên khóa học đã tồn tại',
                'name.max' => 'Tên khóa học không được vượt quá 100 ký tự',
                'description.required' => 'Mô tả khóa học không được trống',
                'description.max' => 'Mô tả khóa học không được vượt quá 500 ký tự',
                'level.required' => 'Cấp độ khóa học không được trống',
                'level.integer' => 'Cấp độ khóa học phải là một số nguyên',
                'level.unique' => 'Cấp độ khóa học đã tồn tại',
                'price.required' => 'Giá khóa học không được trống',
                'price.numeric' => 'Giá khóa học phải là một số',
                'subject_id.required' => 'ID môn học không được trống',
                'subject_id.exists' => 'ID môn học không tồn tại trong danh sách môn học',
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
                'subject_id' => $request->subject_id,
                'updated_by' => $authController->getEmail()
            ]);

            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $course = Course::find($id);

            if (!$course) {
                return response()->json([
                    'error_message' => 'Không tìm thấy khóa học'
                ], 404);
            }

            $course->delete();

            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                "created_by" => $course->created_by,
                "updated_by" => $course->updated_by,
                "created_at" => $course->created_at,
                "updated_at" => $course->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
