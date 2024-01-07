<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Test;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AnswerController extends Controller
{

    public function getAnswer()
    {
        $answers = Answer::get();
        return response()->json([
            'answers' => $answers
        ], 200);
    }

    public function getAnswers()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $answers = Answer::orderByDesc('test_id')->paginate(10);

            return response()->json([
                'answers' => $this->customanswers($answers->items()),
                'totalPage' => $answers->lastPage(),
                'pageNum' => $answers->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customanswers($answers)
    {
        $result = [];
        foreach ($answers as $answer) {
            $serialAnswer = "";
            if ($answer->test_id !== "") {
                $test = Test::find($answer->test_id);
                $serialAnswer = $test->serial_answer;
            }
            $data = [
                "id" => $answer->id,
                "answer_test" => $answer->answer_test,
                "serialAnswer" => $answer->serial_answer,
                "test_id" => $answer->test_id,
                "serial_answer" => $serialAnswer,
                "created_by" => $answer->created_by,
                "updated_by" => $answer->updated_by,
                "created_at" => $answer->created_at,
                "updated_at" => $answer->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }

    public function insertAnswer(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Answer_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'answerTest' => [
                    'required',
                    Rule::unique('answers', 'answer_test')->where(function ($query) use ($request) {
                        return $query->where('test_id', $request->test_id);
                    }),
                    'max:255',
                ],
                'serialAnswer' => [
                    'required',
                    'numeric',
                    'unique:answers,serial_answer,NULL,id,test_id,' . $request->test_id,
                ],
            ], [
                'answerTest.required' => 'The answer must not be blank',
                'answerTest.unique' => 'The answer must not be the same',
                'answerTest.max' => 'Answers must be no longer than 255 characters',
                'serialAnswer.required' => 'The answer position must not be blank',
                'serialAnswer.numeric' => 'The answer position must be numeric',
                'serialAnswer.unique' => 'The answer position must not coincide',
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            if ($request->test_id != null) {
                $test = Test::find($request->test_id);
                if ($test == null) {
                    return response()->json([
                        'error_message' => 'The test is incorrect'
                    ], 400);
                }
            }
            Answer::create([
                'answer_test' => $request->answerTest,
                'serial_answer' => $request->serialAnswer,
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $request->input('test_id', 1)
            ]);
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function updateAnswer(Request $request, string $id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'message' => 'You have no rights.'
                ], 401);
            }
            $answer = Answer::find($id);
            if ($answer == null) {
                return response()->json([
                    'error_message' => 'No test found'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'answerTest' => [
                    'required',
                    Rule::unique('answers', 'answer_test')->where(function ($query) use ($request) {
                        return $query->where('test_id', $request->test_id);
                    }),
                    'max:255',
                ],
                'serialAnswer' => [
                    'required',
                    'numeric',
                    'unique:answers,serial_answer,NULL,id,test_id,' . $request->test_id,
                ],
            ], [
                'answerTest.required' => 'The answer must not be blank',
                'answerTest.unique' => 'The answer must not be the same',
                'answerTest.max' => 'Answers must be no longer than 255 characters',
                'serialAnswer.required' => 'The answer position must not be blank',
                'serialAnswer.numeric' => 'The answer position must be numeric',
                'serialAnswer.unique' => 'The answer position must not coincide',
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            if ($request->test_id != null) {
                $test = Test::find($request->test_id);
                if ($test == null) {
                    return response()->json([
                        'error_message' => 'incorrect test'
                    ], 400);
                }
            }
            $answer->serial_answer = $request->answerTest;
            $answer->answer_test = $request->serialAnswer;
            $answer->test_id = $request->input('test_id', 1);
            $answer->updated_by = $authController->getEmail();
            $answer->created_by = $authController->getEmail();
            $answer->save();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function deleteAnser($id)
    {
        try {
            $answer = Answer::find($id);
            if ($answer == null) {
                return response()->json([
                    'error_message' => 'No test found'
                ], 400);
            }
            $answer->delete();
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }
}
