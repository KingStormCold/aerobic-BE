<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
class AnswerController extends Controller
{


    public function getAnswer()
    {
        $answers = Answer::get();
        return response()->json([
            'answers' => $answers
        ], 200);
    }
 
    /**
     * Store a newly created resource in storage.
     */
    public function insertAnswer(Request $request)
    {
       try {
            // Xác minh quyền hạn của người dùng
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Answer_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            // Kiểm tra và xử lý dữ liệu đầu vào
            $validator = Validator::make($request->all(), [ 
                'answerTest' => 'required|unique:answers,answer_test|max:255',
                'serialAnswer' => 'required|unique:answers,serial_answer|max:255',
                
            ], [
                'answerTest.required' => 'câu trả lời không được trống',
                'answerTest.unique' => 'câu trả lời không được trùng',
                'serialAnswer.required' => 'đáp án không được trống',
                'serialAnswer.unique' => 'đáp án không được trùng',
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
            // Tạo mới một bai test và lưu vào cơ sở dữ liệu
            Answer::create([
                'answer_test' => $request->answerTest,
                'serial_answer' => $request->serialAnswer,
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $request->input('test_id', 1)
            ]);
            // Trả về phản hồi JSON với kết quả
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function updateAnswer(Request $request, string $id)
    {
            try {
                $authController = new AuthController();
                $isAuthorization = $authController->isAuthorization('ADMIN');
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
                    'answerTest' => 'required|unique:answers,answer_test|max:255',
                    'serialAnswer' => 'required|unique:answers,serial_answer|max:255',
                ], [
                    'answerTest.required' => 'câu trả lời không được trống',
                    'answerTest.unique' => 'câu trả lời không được trùng',
                    'serialAnswer.required' => 'đáp án không được trống',
                    'serialAnswer.unique' => 'đáp án không được trùng',
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
    

    /**
     * Remove the specified resource from storage.
     */
    public function deleteAnser($id)
    {
        try {
            // Tìm bai test theo ID và kiểm tra sự tồn tại
            $answer = Answer::find($id);
            if ($answer == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy bai test'
                ], 400);
                // Xóa bai test khỏi cơ sở dữ liệu
            }
            $answer->delete();

            // Trả về phản hồi JSON với kết quả
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }







    public function getAnswers()
    {
        try {
            $authController = new AuthController();
            $roles = $authController->getRoles();
            $isAuthorization = $authController->isAuthorization('ADMIN');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $answers = Answer::orderByDesc('test_id')->paginate(10);
            
            return response()->json([
                'answers' => $this->customanswers($answers->items()),
                // 'subjects' =>$subjects->items(),
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
                $serialAnswer= $test->serial_answer;
                
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










}
