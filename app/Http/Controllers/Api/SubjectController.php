<?php

namespace App\Http\Controllers\Api;


use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Category;
use App\Models\user;
use App\Http\Controllers\Api\CategoryController;
use Exception;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getSubjects()
    {
        try {
            $authController = new AuthController();
            $roles = $authController->getRoles();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            $subjects = Subject::orderByDesc('category_id')->paginate(10);

            return response()->json([
                'subjects' => $this->customSubjects($subjects->items()),
                // 'subjects' =>$subjects->items(),
                'totalPage' => $subjects->lastPage(),
                'pageNum' => $subjects->currentPage(),
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }


    public function customSubjects($subjects)
    {
        $result = [];
        foreach ($subjects as $subject) {
            $categoryName = "";
            if ($subject->category_id !== "") {
                $category = Category::find($subject->category_id);
                $categoryName = $category->name;
            }
            $data = [
                "id" => $subject->id,
                "content" => $subject->content,
                "name" => $subject->name,
                "image" => $subject->image,
                "category_id" => $subject->category_id,
                "promotional_price" => $subject->promotional_price,
                "category_name" => $categoryName,
                "created_by" => $subject->created_by,
                "updated_by" => $subject->updated_by,
                "created_at" => $subject->created_at,
                "updated_at" => $subject->updated_at
            ];
            array_push($result, $data);
        }
        return $result;
    }


    public function getSubject($id)
    {
        $authController = new AuthController();
        $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
        if (!$isAuthorization) {
            return response()->json([
                'code' => 'SUB_001',
                'message' => 'Bạn không có quyền.'
            ], 401);
        }
        $subject = Subject::find($id);
        if ($subject == null) {
            return response()->json([
                'error_message' => 'Không tìm thấy category'
            ], 400);
        }
        return response()->json([
            'subject' => $subject
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function insertSubject(Request $request)
    {
        try {
            // Xác minh quyền hạn của người dùng
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Subject_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            // Kiểm tra và xử lý dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'subject_content' => 'required',
                'subject_image' => 'required',
                'promotional_price_subject' => 'required|numeric',
                'category_id' => 'required|exists:categories,id'
            ], [
                'subject_content.required' => 'Nội dung không được trống',
                'subject_image.required' => 'Hình ảnh không được trống',
                'promotional_price_subject.required' => 'giá khuyến mãi không được trống',
                'promotional_price_subject.numeric' => 'giá khuyến mãi phai la so',
                'category_id.exists' => 'Danh mục không đúng',
                'category_id.required' => 'danh mục không được trống '
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            $contentValue = $request->input('subject_content', '');
            $subjectName = "";
            if ($request->category_id != null) {
                $category = Category::find($request->category_id);
                if ($category == null) {
                    return response()->json([
                        'error_message' => 'Danh mục cha không đúng  '
                    ], 400);
                }
                $subjectName = $category->name;
            }
            // Tạo mới một môn học và lưu vào cơ sở dữ liệu
            Subject::create([
                'content' => $request->subject_content,
                'image' => $request->subject_image,
                'promotional_price' => $request->promotional_price_subject,
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'name' => $subjectName,
                'category_id' => $request->input('category_id', 1)
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
     * Display the specified resource.
     */
    public function updateSubject($id,  Request $request)
    {
        try {
            // Xác minh quyền hạn của người dùng
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Subject_001',
                    'message' => 'Bạn không có quyền.'
                ], 401);
            }
            // Tìm môn học theo ID và kiểm tra sự tồn tại
            $subject = Subject::find($id);
            if ($subject == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy subject'
                ], 400);
            }
            // Kiểm tra và xử lý dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'subject_content' => 'required',
                'subject_image' => 'required',
                'promotional_price_subject' => 'required|numeric',

                'category_id' => 'required|exists:categories,id'

            ], [
                'subject_content.required' => 'Nội dung không được trống',
                'subject_image.required' => 'Hình ảnh không được trống',
                'promotional_price_subject.required' => 'Giá khuyến mãi không được trống',
                'promotional_price_subject.numeric' => 'Giá khuyến mãi phai la so',
                'category_id.exists' => 'Danh mục không đúng',
                'category_id.required' => 'danh mục không được trống '
            ]);
            // Nếu có lỗi xác minh, trả về thông báo lỗi
            if ($validator->fails()) {
                $errors = $validator->errors()->all();

                $categoryId = $request->input('category_id');
                $categoryName = Category::where('id', $categoryId)->value('name');

                foreach ($errors as $key => $error) {
                    return response()->json([
                        'error_message' => $error
                    ], 400);
                }
            }
            // Kiểm tra sự tồn tại của danh mục cha nếu có
            if ($request->category_id != null) {
                $subjectParent = Category::find($request->category_id);
                if ($subjectParent == null) {
                    return response()->json([
                        'error_message' => 'Danh mục cha không đúng'
                    ], 400);
                }
            }
            // Cập nhật thông tin môn học và lưu vào cơ sở dữ liệu
            $subject->category_id = $request->input('category_id', 1);
            $subject->content = $request->input('subject_content');
            $subject->image = $request->input('subject_image');
            $subject->promotional_price = $request->input('promotional_price_subject');
            $subject->updated_by = auth()->user()->email;
            $subject->save();




            // Trả về phản hồi JSON với kết quả
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function deleteSubject($id)
    {
        try {
            // Tìm môn học theo ID và kiểm tra sự tồn tại
            $subject = Subject::find($id);
            if ($subject == null) {
                return response()->json([
                    'error_message' => 'Không tìm thấy subject'
                ], 400);
                // Xóa môn học khỏi cơ sở dữ liệu
            }
            $subject->delete();

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

    public function showSubject()
    {
        $result = [];
        $subjects = Subject::get();
        foreach ($subjects as $subject) {
            $data = [
                "id" => $subject->id,
                "name" => $subject->name,
            ];
            array_push($result, $data);
        }
        return response()->json([
            'subjects' => $result
        ], 200);
    }
}
