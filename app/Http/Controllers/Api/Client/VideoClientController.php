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
                    'message' => 'Bạn cần dăng kí thành viên và mua khóa học để xem video này.'
                ], 401);
            }

            $videos = Video::where('course_id',$courseId)->orderByDesc('created_at')->get();
            $course = Course::find($courseId);
            if (!$course) {
                return response()->json([
                    'message' => 'Không tìm thấy khóa học.'
                ], 404);
            }
            return response()->json([
                'videos' => $this->customfullVideos($videos),

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
        foreach ($videos as $video) {
            $courseName = "";
            $course = Course::find($video->course_id);
            if ($course) {
                
                $courseName= $course->name;
            }
            $data = [
                "course_id" => $video->course_id,
                "course_name" => $courseName,
                "id_video" => $video->id,
                "name" => $video->name,
                "link_video" => $video->link_video,               
                // "finished" => $video->finished, 
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
