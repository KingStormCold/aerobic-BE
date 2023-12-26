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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $answers = Answer::orderByDesc('test_id')->paginate(10);

            return response()->json([
                'answers' => $this->customanswers($answers->items()),
                'totalPage' => $answers->lastPage(),
                'pageNum' => $answers->currentPage(),
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
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
                'answerTest.required' => 'Câu trả lời không được trống',
                'answerTest.unique' => 'Câu trả lời không được trùng',
                'answerTest.max' => 'Câu trả lời không được dài quá 255 ký tự',
                'serialAnswer.required' => 'Vị trí đáp án không được trống',
                'serialAnswer.numeric' => 'Vị trí đáp án phải là số',
                'serialAnswer.unique' => 'Vị trí đáp án không được trùng',
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
                        'error_message' => 'Bai test không đúng'
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
            return response()->json([
                'error_message' => $e
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $answer = Answer::find($id);
            if ($answer == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy bai test'
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
                'answerTest.required' => 'Câu trả lời không được trống',
                'answerTest.unique' => 'Câu trả lời không được trùng',
                'answerTest.max' => 'Câu trả lời không được dài quá 255 ký tự',
                'serialAnswer.required' => 'Vị trí đáp án không được trống',
                'serialAnswer.numeric' => 'Vị trí đáp án phải là số',
                'serialAnswer.unique' => 'Vị trí đáp án không được trùng',
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
                        'error_message' => 'bai test không đúng'
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
            Log::debug($e);
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function deleteAnser($id)
    {
        try {
            $answer = Answer::find($id);
            if ($answer == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy bai test'
                ], 400);
            }
            $answer->delete();

            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function fullAnswers()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn cần dăng kí thành viên và mua khóa học này.'
                ], 401);
            }

            $answers = Answer::orderByDesc('created_at')->paginate(10);
            return response()->json([
                'answers' => $this->customfullAnswers($answers->items()),

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
                "serial_answer" => $answer->serial_answer,               
                "created_by" => $answer->created_by,
                "updated_by" => $answer->updated_by,
                "created_at" => $answer->created_at,
                "updated_at" => $answer->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
