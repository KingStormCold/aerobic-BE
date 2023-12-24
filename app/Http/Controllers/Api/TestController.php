<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_TEST');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'CATE_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $tests = Test::orderByDesc('created_at')->paginate(10);

            return response()->json([
                'videos' => $this->customTests($tests->items()),
                'totalPage' => $tests->lastPage(),
                'pageNum' => $tests->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function customTests($tests)
    {
        $result = [];

        foreach ($tests as $test) {
            $videoName = "";  // Khởi tạo $videoName với giá trị mặc định
            
            if ($test->video_id !== "") {
                $video = Video::find($test->video_id);
        
                // Kiểm tra xem có video được tìm thấy hay không
                if ($video) {
                    $videoName = $video->name;
                }
            }
        
            $data = [
                "id" => $test->id,
                "test_content" => $test->test_content,
                "serial_answer" => $test->serial_answer,
                "video_id" => $test->video_id,
                "video_name" => $video->name,
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $test = Test::find($id);

            if (!$test) {
                return response()->json([
                    'error_message' => 'Không tìm thấy bài test'
                ], 404);
            }

            return response()->json([
                'test' => $test
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'test_content' => 'required|unique:tests,test_content',
                'serial_answer' => 'required|numeric|between:1,4',
                'video_id' => 'required|numeric|exists:videos,id'
            ], [
                'test_content.required' => 'test_content không được trống',
                'test_content.unique' => 'test_content ko dc trùng',
                'serial_answer.required' => 'serial_answer ko dc trống',
                'serial_answer.numeric' => 'serial_answer phải là số',
                'serial_answer.between' => 'serial_answer chỉ từ 1->4',
                'video_id.numeric'=> 'video_id phải là số',
                'video_id.required' => 'video_id ko dc trống',
                'video_id.exists' => 'nguồn video ko đúng'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            $test = Test::create([
                'test_content' => $request->test_content,
                'serial_answer' => $request->serial_answer,
                'created_by' => auth()->user()->email,
                'video_id' => $request->video_id,
            ]);
           
            

            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $test = Test::find($id);

            if (!$test) {
                return response()->json([
                    'error_message' => 'Không tìm thấy bài test'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'test_content' => 'required|unique:tests,test_content,'.$test->id,
                'serial_answer' => 'required|numeric|between:1,4',
            ], [
                'test_content.required' => 'test_content không được trống',
                'test_content.unique' => 'test_content ko dc trùng',
                'serial_answer.between' => 'serial_answer chỉ từ 1->4',
                'serial_answer.required' => 'serial_answer ko dc trống',
                'serial_answer.numeric' => 'serial_answer phải là số'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error_message' => $validator->errors()->first()
                ], 400);
            }

            $userProfileResponse = $authController->userProfile();
            $userProfileData = json_decode($userProfileResponse->getContent(), true);

            if ($userProfileResponse->getStatusCode() !== 200 || !isset($userProfileData['data']['email'])) {
                return response()->json([
                    'error_message' => 'Không thể lấy thông tin hồ sơ người dùng'
                ], 400);
            }
            
            $updatedBy = $userProfileData['data']['email'];

            $test->update([
                'test_content' => $request->test_content,
                'serial_answer' => $request->serial_answer,
                'updated_by' => $updatedBy,
            ]);
        

            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
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
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }

            $test = Test::find($id);

            if (!$test) {
                return response()->json([
                    'error_message' => 'Không tìm thấy vài test'
                ], 404);
            }

            $test->delete();

            return response()->json([
                'result' => 'success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function showVideoName()
    {
        $result = [];
        $videos = Video::get();
        foreach ($videos as $video) {
            $data = [
                "id" => $video->id,
                "name" => $video->name,
            ];
            array_push($result, $data);
        }
        return response()->json([
            'videos' => $result
        ], 200);
    }
}
