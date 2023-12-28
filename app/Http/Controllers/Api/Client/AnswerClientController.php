<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Test;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\AuthController;

class AnswerClientController extends Controller
{
    public function fullAnswers($testId)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn cần đăng kí thành viên và mua khóa học để xem câu trả lời'
                ], 401);
            }

            $answers = Answer::where('test_id', $testId)->orderByDesc('created_at')->get();
            $test = Test::find($testId);
            if (!$test) {
                return response()->json([
                    'message' => 'Không tìm thấy bài kiểm tra.'
                ], 400);
            }
            return response()->json([
                'tests' => $this->customfullAnswers($answers),

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customfullAnswers($answers)
    {
        $result = [];
        $testId = null;
        $testName = "";
        $serialAnswer = "";
        $answerArray = [];

        foreach ($answers as $answer) {
            if ($testId != $answer->test_id) {
                if ($testId != null) {
                    $data = [
                        "test_id" => $testId,
                        "test_content" => $testName,
                        "serial_answer" => $serialAnswer,
                        "answers" =>  $answerArray
                    ];
                    $result = $data;
                    $answerArray = [];
                }
                $testId = $answer->test_id;
                $test = Test::find($testId);
                if ($test) {
                    $testName = $test->test_content;
                    $serialAnswer = $test->serial_answer;
                }
            }
            $answerData = [
                "answer_id" => $answer->id,
                "answer_content" => $answer->answer_test,
                "serial_answer" => $answer->serial_answer,
            ];
            array_push($answerArray, $answerData);
        }

        // Thêm dữ liệu cuối cùng
        if ($testId != null) {
            $data = [
                "test_id" => $testId,
                "test_content" => $testName,
                "serial_answer" => $serialAnswer,
                "answers" =>  $answerArray
            ];
            $result = $data;
        }

        return $result;
    }
}
