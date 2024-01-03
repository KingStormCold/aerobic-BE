<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;
use App\Models\VideoUser;
use Illuminate\Support\Facades\Auth;

class VideoClientController extends Controller
{
    public function fullVideos($courseId)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn cần đăng kí thành viên và mua khóa học để xem bài kiểm tra'
                ], 401);
            }

            $videos = Video::where('course_id', $courseId)->orderByDesc('created_at')->get();
            $course = Course::find($courseId);
            if (!$course) {
                return response()->json([
                    'message' => 'Không tìm thấy khóa học.'
                ], 400);
            }
            return response()->json([
                'courses' => $this->customfullVideos($videos),

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customfullVideos($videos)
    {
        $result = [];
        $courseId = null;
        $courseName = "";
        $videoArray = [];

        foreach ($videos as $video) {
            if ($courseId != $video->course_id) {
                if ($courseId != null) {
                    $data = [
                        "course_id" => $courseId,
                        "courseName" => $courseName,
                        "videos" =>  $videoArray
                    ];
                    $result = $data;
                    $videoArray = [];
                }
                $courseId = $video->course_id;
                $course = Course::find($courseId);
                if ($course) {
                    $courseName = $course->name;
                }
            }
            $videosUsers = VideoUser::where('users_id', Auth::id())->where('videos_id', $video->id)->first();
            $progress = 0;
            $previousTime = 0;
            $totalCorrect = 0;
            if ($videosUsers !== null) {
                $progress = $videosUsers->progress;
                $previousTime = $videosUsers->previous_time;
                $totalCorrect = $videosUsers->total_correct;
            }

            $courseData = [
                "video_id" => $video->id,
                "videoName" => $video->name,
                "link_video" => $video->link_video,
                "full_time" => $video->full_time,
                "view" => $video->view,
                "progress" => $progress,
                "previous_time" => $previousTime,
                "total_correct" => $totalCorrect,
                "finished"  => $video->finished,
            ];
            array_push($videoArray, $courseData);
        }

        // Thêm dữ liệu cuối cùng
        if ($courseId != null) {
            $data = [
                "course_id" => $courseId,
                "courseName" => $courseName,
                "videos" =>  $videoArray
            ];
            $result = $data;
        }

        return $result;
    }

    public function countVideo($videoId)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'Bạn cần đăng kí thành viên và mua khóa học để xem bài kiểm tra'
                ], 401);
            }
            $video = Video::find($videoId);
            if ($video !== null) {
                $count = $video->view;
                $video->update([
                    'view' => $count + 1
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function updateVideoUser(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'Bạn cần đăng kí thành viên và mua khóa học để xem bài kiểm tra'
                ], 401);
            }
            $videosUsers = VideoUser::where('users_id', Auth::id())->where('videos_id', $request->video_id)->first();
            if ($videosUsers !== null) {
                if ($request->previous_time > $videosUsers->previous_time) {
                    $videosUsers->update([
                        'previous_time' => $request->previous_time,
                        'progress' => $request->progress
                    ]);
                }
            } else {
                VideoUser::create([
                    'users_id' => Auth::id(),
                    'videos_id' => $request->video_id,
                    'previous_time' => $request->previous_time,
                    'progress' => $request->progress,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
}
