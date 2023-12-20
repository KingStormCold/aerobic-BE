<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    public function getVideo()
    {
        $videos = Video::where('course_id', '>', "0")->get();
        return response()->json([
            'videos' => $videos
        ], 200);
    }

    public function Videos()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_VIDEO');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $videos = Video::orderByDesc('course_id')->paginate(10);

            return response()->json([
                'courses' => $this->customVideos($videos->items()),
                'totalPage' => $videos->lastPage(),
                'pageNum' => $videos->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function customVideos($videos)
    {
        $result = [];

        foreach ($videos as $video) {
            $courseName = "";
            if ($video->course_id !== "") {
                $course = Course::find($video->course_id);
                $courseName = $course->name;
            }
            $data = [
                "id" => $video->id,
                "name" => $video->name,
                "link_image" => $video->link_image,
                "course_id" => $video->course_id,
                "course_name" => $courseName,
                "finished" => $video->finished,
                "created_by" => $video->created_by,
                "updated_by" => $video->updated_by,
                "created_at" => $video->created_at,
                "updated_at" => $video->updated_at,
            ];

            array_push($result, $data);
        }

        return $result;
    }

    public function showVideos($id)
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

            $video = Video::find($id);

            if (!$video) {
                return response()->json([
                    'error_message' => 'Không tìm thấy khóa học'
                ], 404);
            }

            return response()->json([
                'videos' => $video
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function insertVideo(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_VIDEO');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                "id" => 'required',
                "name" => 'required',
                "link_image" => 'required',
                "course_id" => 'required',
                "finished" => 'required',
            ], [
                'id.required' => 'ID video không được để trống',
                'name.required' => 'Tên video không được để trống',
                'link_image.required' => 'Link ảnh không được để trống',
                'course_id.required' => 'không được để trống id khóa học',
                'finished.required' => 'Không được để trống mục đã hoàn thành',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }

            Video::create([
                'id' => $request->id,
                'name' => $request->name,
                'link_image' => $request->link_image,
                'finished' => $request->finished,
                'course_id' => $request->course_id,
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

    public function updateVideo(Request $request, $id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_VIDEO');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $video = Video::find($id);

            if (!$video) {
                return response()->json([
                    'error_message' => 'Không tìm thấy video'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:videos,name,' . $video->id,
                'link_image' => 'required',
                'finished' => 'required',
                'course_id' => 'required|exists:courses,id',
            ], [
                'name.required' => 'Tên video không được trống',
                'name.unique' => 'Tên video đã tồn tại',
                'name.max' => 'Tên video không được vượt quá 100 ký tự',
                'link_image.required' => 'Link ảnh không được trống',
                'finished.required' => 'Không được để trống mục đã hoàn thành',
                'course_id.required' => 'ID khóa học không được trống',
                'course_id.exists' => 'ID khóa học không tồn tại trong danh sách khóa học',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }

            $video->update([
                'name' => $request->name,
                'link_image' => $request->link_image,
                'finished' => $request->finished,
                'course_id' => $request->course_id,
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

    public function deleteVideo($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_VIDEO');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $video = Video::find($id);

            if (!$video) {
                return response()->json([
                    'error_message' => 'Không tìm thấy khóa học'
                ], 404);
            }

            $video->delete();

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
