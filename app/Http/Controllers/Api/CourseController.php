<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\Subject;
use Exception;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function getParentCourse()
    {
        $courses = Course::where('subject_id', '>', "0")->get();
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
                'Course_name' => 'required|string|max:100|unique:courses,name',
                'Course_description' => 'required|string|max:500',
                'Course_level' => 'required|integer|unique:courses,level',
                'Course_price' => 'required|numeric',
                'Course_subject_id' => 'required|exists:subjects,id',
            ], [
                'Course_name.required' => 'Tên khóa học không được trống',
                'Course_name.unique' => 'Tên khóa học đã tồn tại',
                'Course_name.max' => 'Tên khóa học không được vượt quá 100 ký tự',
                'Course_description.required' => 'Mô tả khóa học không được trống',
                'Course_description.max' => 'Mô tả khóa học không được vượt quá 500 ký tự',
                'Course_level.required' => 'Cấp độ khóa học không được trống',
                'Course_level.integer' => 'Cấp độ khóa học phải là một số nguyên',
                'Course_level.unique' => 'Cấp độ khóa học đã tồn tại',
                'Course_price.required' => 'Giá khóa học không được trống',
                'Course_price.numeric' => 'Giá khóa học phải là một số',
                'Course_subject_id.required' => 'ID môn học không được trống',
                'Course_subject_id.exists' => 'ID môn học không tồn tại trong danh sách môn học',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }

            if ($request->subject_id != null) {
                $Course = Course::find($request->subject_id);
                if ($Course == null) {
                    return response()->json([
                        'error_message' => 'Danh mục cha không đúng'
                    ], 400);
                }
            }

            Course::create([
                'name' => $request->Course_name,
                'description' => $request->Course_description,
                'level' => $request->Course_level,
                'price' => $request->Course_price,
                'subject_id' => $request->Course_subject_id,
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
                'Course_name' => 'required|string|max:100|unique:courses,name,' . $course->id,
                'Course_description' => 'required|string|max:500',
                'Course_level' => 'required|integer',
                'Course_price' => 'required|numeric',
                'Course_subject_id' => 'required|exists:subjects,id',
            ], [
                'Course_name.required' => 'Tên khóa học không được trống',
                'Course_name.unique' => 'Tên khóa học đã tồn tại',
                'Course_name.max' => 'Tên khóa học không được vượt quá 100 ký tự',
                'Course_description.required' => 'Mô tả khóa học không được trống',
                'Course_description.max' => 'Mô tả khóa học không được vượt quá 500 ký tự',
                'Course_level.required' => 'Cấp độ khóa học không được trống',
                'Course_level.integer' => 'Cấp độ khóa học phải là một số nguyên',
                'Course_price.required' => 'Giá khóa học không được trống',
                'Course_price.numeric' => 'Giá khóa học phải là một số',
                'Course_subject_id.required' => 'ID môn học không được trống',
                'Course_subject_id.exists' => 'ID môn học không tồn tại trong danh sách môn học',
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
}
