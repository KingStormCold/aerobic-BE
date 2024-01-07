<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Log;
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
                    'message' => 'You have no rights.'
                ], 401);
            }

            $videos = Video::where('course_id', $id)->where('status', 1)->paginate(10);

            return response()->json([
                'courses' => $this->customVideos($videos->items()),
                'totalPage' => $videos->lastPage(),
                'pageNum' => $videos->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] get videos ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                "free" => $video->free,
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
                    'message' => 'You have no rights.'
                ], 401);
            }
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'error_message' => 'Video information not found'
                ], 404);
            }
            return response()->json([
                'videos' => $video
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] show video ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                    'message' => 'You have no rights.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'link_video' => 'required|string|max:255',
                'course_id' => 'required|exists:courses,id',
                'finished' => 'required',
            ], [
                'name.required' => 'Video name cant be blank',
                'name.max' => 'Video name cant exceed 255 characters',
                'link_video.required' => 'Image links must not be blank',
                'link_video.max' => 'Photo links must not exceed 255 characters',
                'course_id.required' => 'Course IDs cant be left blank',
                'course_id.exists' => 'Course ID does not exist in the course list',
                'finished.required' => 'Completed items cant be left blank',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            Video::create([
                'name' => $request->name,
                'link_video' => $request->link_video,
                'course_id' => $request->course_id,
                'free' => $request->free,
                'created_by' => $authController->getEmail(),
                'full_time' => $request->full_time,
                'view' => 0
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] insert video ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                    'message' => 'You have no rights.'
                ], 401);
            }
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'error_message' => 'Video not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'link_video' => 'required|string|max:255',
                'finished' => 'required',
                'course_id' => 'required|exists:courses,id',
            ], [
                'name.required' => 'The video name cant be blank',
                'name.max' => 'Video name cant exceed 255 characters',
                'link_video.required' => 'The image link should not be blank',
                'link_video.max' => 'Photo links must not exceed 255 characters',
                'finished.required' => 'Completed items cant be left blank',
                'course_id.required' => 'Course IDs cant be blank',
                'course_id.exists' => 'Course ID does not exist in the course list',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            $video->update([
                'name' => $request->name,
                'link_video' => $request->link_video,
                'course_id' => $request->course_id,
                'free' => $request->free,
                'updated_by' => $authController->getEmail(),
                'full_time' => $request->full_time,
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] update video ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                    'message' => 'You have no rights.'
                ], 401);
            }
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'error_message' => 'Video Not Found'
                ], 404);
            }
            $video->update([
                'status' => 0
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] delete video ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
