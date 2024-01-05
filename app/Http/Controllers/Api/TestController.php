<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    public function getTests()
    {
        $tests = Test::where('video_id', '>', "0")->get();
        return response()->json([
            'tests' => $tests
        ], 200);
    }

    public function test($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $tests = Test::with('answers')->where('video_id', $id)->paginate(10);
            return response()->json([
                'videos' => $this->customTests($tests->items()),
                'totalPage' => $tests->lastPage(),
                'pageNum' => $tests->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function customTests($tests)
    {
        $result = [];
        foreach ($tests as $test) {
            $videoName = "";
            if ($test->video_id !== "") {
                $video = Video::find($test->video_id);
                $videoName = $video->name;
            }
            $answerList = [];
            foreach ($test->answers as $answer) {
                $answerData = [
                    "id" => $answer->id,
                    "answer_content" => $answer->answer_test
                ];
                array_push($answerList, $answerData);
            }
            $data = [
                "id" => $test->id,
                "test_content" => $test->test_content,
                "serial_answer" => $test->serial_answer,
                "video_id" => $test->video_id,
                "video_name" => $videoName,
                "answers" => $answerList,
                "created_by" => $test->created_by,
                "updated_by" => $test->updated_by,
                "created_at" => $test->created_at,
                "updated_at" => $test->updated_at,
            ];
            array_push($result, $data);
        }
        return $result;
    }

    public function showTests($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $test = Test::find($id);
            if (!$test) {
                return response()->json([
                    'error_message' => 'No test found'
                ], 404);
            }
            return response()->json([
                'test' => $test
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function insertTest(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'test_content' => 'required|unique:tests,test_content',
                'serial_answer' => 'required|numeric',
                'video_id' => 'required|numeric|exists:videos,id',
                'answer_1' => 'required',
                'answer_2' => 'required',
                'answer_3' => 'required',
                'answer_4' => 'required'
            ], [
                'test_content.required' => 'test_content must not be empty',
                'test_content.unique' => 'test_content not duplicate',
                'serial_answer.required' => 'serial_answer is not empty',
                'serial_answer.numeric' => 'serial_answer must be a number',
                'video_id.required' => 'video_id is not empty',
                'video_id.exists' => 'The video source is incorrect',
                'answer_1.required' => 'Answer 1 should not be blank',
                'answer_2.required' => 'Answer 2 should not be blank',
                'answer_3.required' => 'Answer 3 should not be blank',
                'answer_4.required' => 'Answer 4 should not be blank',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            $test = Test::create([
                'test_content' => $request->test_content,
                'serial_answer' => $request->serial_answer,
                'video_id' => $request->video_id,
                'created_by' => $authController->getEmail()
            ]);
            Answer::create([
                'answer_test' => $request->answer_1,
                'serial_answer' => '1',
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $test->id
            ]);
            Answer::create([
                'answer_test' => $request->answer_2,
                'serial_answer' => '2',
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $test->id
            ]);
            Answer::create([
                'answer_test' => $request->answer_3,
                'serial_answer' => '3',
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $test->id
            ]);
            Answer::create([
                'answer_test' => $request->answer_4,
                'serial_answer' => '4',
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'test_id' => $test->id
            ]);
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function updateTest(Request $request, $id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $test = Test::find($id);
            if (!$test) {
                return response()->json([
                    'error_message' => 'No test found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'test_content' => 'required|unique:tests,test_content,' . $test->id,
                'serial_answer' => 'required|numeric',
                'video_id' => 'required|numeric|exists:videos,id',
                'answers' => 'required'
            ], [
                'test_content.required' => 'test_content must not be empty',
                'test_content.unique' => 'test_content not duplicate',
                'serial_answer.required' => 'serial_answer is not empty',
                'serial_answer.numeric' => 'serial_answer must be a number',
                'video_id.required' => 'video_id is not empty',
                'video_id.numeric' => 'video_id must be a number',
                'video_id.exists' => 'Incorrect video source'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }
            $test->update([
                'test_content' => $request->test_content,
                'serial_answer' => $request->serial_answer,
                'video_id' => $request->video_id,
                'updated_by' => $authController->getEmail()
            ]);
            foreach ($request->answers as $answer) {
                $answerId = $answer['id'];
                $updateAnswer = Answer::find($answerId);
                if ($updateAnswer !== null) {
                    $updateAnswer->answer_test = $answer['answer_content'];
                    $updateAnswer->updated_by = $authController->getEmail();
                    $updateAnswer->created_by = $authController->getEmail();
                    $updateAnswer->save();
                }
            }
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function deleteTest($id)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You have no rights.'
                ], 401);
            }
            $test = Test::find($id);
            if (!$test) {
                return response()->json([
                    'error_message' => 'No tests found'
                ], 404);
            }
            $test->delete();
            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }

    public function showTestName()
    {
        $result = [];
        $tests = Test::get();
        foreach ($tests as $test) {
            $data = [
                "id" => $test->id,
                "test_content" => $test->test_content,
            ];
            array_push($result, $data);
        }
        return response()->json([
            'tests' => $result
        ], 200);
    }

    public function fullTests()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'You need to register as a member and purchase this course.'
                ], 401);
            }
            $tests = Test::orderByDesc('created_at')->paginate(10);
            return response()->json([
                'tests' => $this->customfullTests($tests->items()),

            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                "serial_answer" => $test->serial_answer,               
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
