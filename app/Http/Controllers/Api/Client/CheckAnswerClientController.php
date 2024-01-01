<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Test;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;

class CheckAnswerClientController extends Controller
{
    public function checkAnswers(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn cần đăng ký thành viên và mua khóa học để làm bài kiểm tra'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'test_id' => 'required|exists:tests,id',
                'serial_answer' => 'required|numeric|between:1,4', 
            ],[
                'test_id.required' => 'test_id không được trống',
                'test_id.exists' => 'nguồn test_id không đúng',
                'serial_answer.required' => 'serial_answer không được trống',
                'serial_answer.numeric' => 'serial_answer phải là số',
                'serial_answer.between' => 'serial_answer là số từ 1->4',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $testId = $request->input('test_id');
            $correctSerialAnswers = Test::where('id', $testId)->pluck('serial_answer')->toArray();

            $selectedSerialAnswer = $request->input('serial_answer');

            $isCorrect = in_array($selectedSerialAnswer, $correctSerialAnswers);
            $message = $isCorrect ? 'Đúng rồi' : 'Sai rồi';

            return response()->json([
                'is_correct' => $isCorrect,
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    
    
}