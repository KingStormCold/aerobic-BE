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

            $answers = Answer::where('test_id',$testId)->orderByDesc('created_at')->get();
            $test = Test::find($testId);
            if (!$test) {
                return response()->json([
                    'message' => 'Không tìm thấy bài test.'
                ], 404);
            }
            return response()->json([
                'answers' => $this->customfullAnswers($answers),

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
        foreach ($answers as $answer) {
            $testName = "";
            $test = Test::find($answer->test_id);
            if ($test) {
                
                $testName= $test->name;
            }
            $data = [
                "test_id" => $answer->test_id,
                "test_name" => $testName,
                "id_test" => $answer->id,
                "answer_test" => $answer->answer_test,
                // "serial_answer" => $answer->serial_answer,               
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
