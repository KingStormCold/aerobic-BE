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

    public function videos($id)
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

            $videos = Video::where('course_id', $id)->paginate(10);

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
                "link_video" => $video->link_video,
                "course_id" => $video->course_id,
                "course_name" => $courseName,
                "full_time" => $video->full_time,
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
                    'error_message' => 'Không tìm thấy thông tin video'
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
                'name' => 'required|string|max:255|unique:videos,name',
                'link_video' => 'required|string|max:255',
                'course_id' => 'required|exists:courses,id',
                'finished' => 'required',
            ], [
                'name.required' => 'Tên video không được để trống',
                'name.unique' => 'Tên video đã tồn tại',
                'name.max' => 'Tên video không được vượt quá 255 ký tự',
                'link_video.required' => 'Link ảnh không được để trống',
                'link_video.max' => 'Link ảnh không được vượt quá 255 ký tự',
                'course_id.required' => 'không được để trống id khóa học',
                'course_id.exists' => 'ID khóa học không tồn tại trong danh sách khóa học',
                'finished.required' => 'Không được để trống mục đã hoàn thành',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }

            Video::create([
                'name' => $request->name,
                'link_video' => $request->link_video,
                'finished' => $request->finished,
                'course_id' => $request->course_id,
                'created_by' => $authController->getEmail(),
                'full_time' => $request->full_time,
                'view' => 0
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
                'name' => 'required|string|max:255|unique:videos,name,' . $video->id,
                'link_video' => 'required|string|max:255',
                'finished' => 'required',
                'course_id' => 'required|exists:courses,id',
            ], [
                'name.required' => 'Tên video không được trống',
                'name.unique' => 'Tên video đã tồn tại',
                'name.max' => 'Tên video không được vượt quá 255 ký tự',
                'link_video.required' => 'Link ảnh không được trống',
                'link_video.max' => 'Link ảnh không được vượt quá 255 ký tự',
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
                'link_video' => $request->link_video,
                'finished' => $request->finished,
                'course_id' => $request->course_id,
                'updated_by' => $authController->getEmail(),
                'full_time' => $request->full_time,
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
                    'error_message' => 'Không tìm thấy Video'
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

    public function showVideoName()
    {
        $result = [];
        $videos = Video::get();
        foreach ($videos as $video) {
            $data = [
                "id" => $video->id,
                "name" => $video->name,
            ];
            array_push($result, $data);
        }
        return response()->json([
            'videos' => $result
        ], 200);
    }
}
