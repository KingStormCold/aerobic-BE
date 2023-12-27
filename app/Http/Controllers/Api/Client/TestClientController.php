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
                    'message' => 'Bạn cần dăng kí thành viên và mua khóa học để làm bài test.'
                ], 401);
            }

            $tests = Test::where('video_id',$videoId)->orderByDesc('created_at')->get();
            $video = Video::find($videoId);
            if (!$video) {
                return response()->json([
                    'message' => 'Không tìm thấy video.'
                ], 404);
            }
            return response()->json([
                'tests' => $this->customfullTests($tests),

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
        foreach ($tests as $test) {
            $videoName = "";
            $video = Video::find($test->video_id);
            if ($video) {
                
                $videoName= $video->name;
            }
            $data = [
                "video_id" => $test->video_id,
                "video_name" => $videoName,
                "id_video" => $test->id,
                "test_content" => $test->test_content,
                // "serial_answer" => $test->serial_answer,               
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
