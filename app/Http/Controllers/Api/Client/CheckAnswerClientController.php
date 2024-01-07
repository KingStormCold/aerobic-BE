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
use App\Models\Video;
use App\Models\VideoUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
                    'message' => 'You need to sign up for a membership and purchase a course to take the test'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'quizs' => 'required',
            ], [
                'quizs.required' => 'The question and answer list cant be blank',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
            $quizs = $request->input('quizs');
            $testArray = [];
            $totalCorrect = 0;
            $testId = 0;
            foreach ($quizs as $quiz) {
                $test = Test::find($quiz['test_id']);
                $testId = $test->id;
                $answerList = [];
                $answers = Answer::where('test_id', $test->id)->get();
                foreach ($answers as $answer) {
                    $data = [
                        "answer_test" => $answer->answer_test,
                        "checked" => $answer->serial_answer === $quiz['serial_answer']
                    ];
                    array_push($answerList, $data);
                }
                $isCorrect = false;
                if ($test->serial_answer === $quiz['serial_answer']) {
                    $totalCorrect++;
                    $isCorrect = true;
                }
                $testData = [
                    "test_content" => $test->test_content,
                    "isCorrect" => $isCorrect,
                    "answers" => $answerList
                ];
                array_push($testArray, $testData);
            }
            if ($testId !== 0) {
                $testDetail = Test::find($testId);
                $videosUsers = VideoUser::where('users_id', Auth::id())->where('videos_id', $testDetail->video_id)->first();
                if ($videosUsers !== null) {
                    if ($totalCorrect > $videosUsers->total_correct) {
                        $videosUsers->update([
                            'total_correct' => $totalCorrect,
                        ]);
                    }
                    if ($totalCorrect >= 5) {
                        $videoUpdate = Video::find($testDetail->video_id);
                        $videoUpdate->update([
                            'finished' => 1
                        ]);
                    }
                }
            }
            return response()->json([
                'result' => [
                    'tests' => $testArray,
                    'total_correct' => $totalCorrect
                ]
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
}
