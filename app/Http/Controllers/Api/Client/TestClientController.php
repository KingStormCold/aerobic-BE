<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;

class TestClientController extends Controller
{
    public function fullTests($videoId)
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

            $tests = Test::where('video_id',$videoId)->orderByDesc('created_at')->get();
            $video = Video::find($videoId);
            if (!$video) {
                return response()->json([
                    'message' => 'Không tìm thấy video.'
                ], 400);
            }
            return response()->json([
                'videos' => $this->customfullTests($tests),

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customfullTests($tests)
    {
        $result = [];
        $videoId = null;
        $videoName = "";
        $linkVideo = "";
        $testArray = [];

        foreach ($tests as $test) {
            if ($videoId != $test->video_id) {
                if ($videoId != null) {
                    $data = [
                        "video_id" => $videoId,
                        "videoName" => $videoName,
                        "link_video" => $linkVideo,
                        "tests" =>  $testArray
                    ];
                    $result = $data;
                    $testArray = [];
                }
                $videoId = $test->video_id;
                $video = Video::find($videoId);
                if ($video) {
                    $videoName = $video->name;
                    $linkVideo = $video->link_video;
                }
            }
            $testData = [
                "test_id" => $test->id,
                "test_content" => $test->test_content,
                "serial_answer" => $test->serial_answer, 
            ];
            array_push($testArray, $testData);
        }

        // Thêm dữ liệu cuối cùng
        if ($videoId != null) {
            $data = [
                "video_id" => $videoId,
                "videoName" => $videoName,
                "link_video" => $linkVideo,
                "tests" =>  $testArray
            ];
            $result = $data;
        }

        return $result;
    }
}
