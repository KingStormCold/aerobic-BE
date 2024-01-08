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
use Illuminate\Support\Facades\Log;

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
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $subjects = Subject::orderByDesc('created_at')->paginate(10);

            return response()->json([
                'subjects' => $this->customSubjects($subjects->items()),
                // 'subjects' =>$subjects->items(),
                'totalPage' => $subjects->lastPage(),
                'pageNum' => $subjects->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
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
                "status" => $subject->status,
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
                'error_message' => 'You have no rights.'
            ], 401);
        }
        $subject = Subject::find($id);
        if ($subject == null) {
            return response()->json([
                'error_message' => 'Category not found'
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
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Subject_001',
                    'error_message' => 'Bạn không có quyền.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'subject_content' => 'required',
                'subject_image' => 'required',
                'promotional_price_subject' => 'required|numeric',
                'category_id' => 'required|exists:categories,id'
            ], [
                'subject_content.required' => 'Content cant be blank',
                'subject_image.required' => 'Image cant be blank',
                'promotional_price_subject.required' => 'The promotional price cannot be empty',
                'promotional_price_subject.numeric' => 'The promotional price must be numeric',
                'category_id.exists' => 'Incorrect category',
                'category_id.required' => 'The category cant be blank'
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
            Subject::create([
                'content' => $request->subject_content,
                'image' => $request->subject_image,
                'promotional_price' => $request->promotional_price_subject,
                'created_by' => auth()->user()->email,
                'updated_by' => '',
                'name' => $subjectName,
                'status' => $request->status,
                'category_id' => $request->input('category_id', 1)
            ]);
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
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
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'Subject_001',
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $subject = Subject::find($id);
            if ($subject == null) {
                return response()->json([
                    'error_message' => 'No subject found'
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'subject_content' => 'required',
                'subject_image' => 'required',
                'promotional_price_subject' => 'required|numeric',
                'category_id' => 'required|exists:categories,id'
            ], [
                'subject_content.required' => 'Content cant be blank',
                'subject_image.required' => 'Image cant be blank',
                'promotional_price_subject.required' => 'Promo prices cannot be empty',
                'promotional_price_subject.numeric' => 'The promotional price must be numerical',
                'category_id.exists' => 'Incorrect category',
                'category_id.required' => 'The category cant be blank '
            ]);
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
            if ($request->category_id != null) {
                $subjectParent = Category::find($request->category_id);
                if ($subjectParent == null) {
                    return response()->json([
                        'error_message' => 'Incorrect parent category'
                    ], 400);
                }
            }
            $subject->category_id = $request->input('category_id', 1);
            $subject->content = $request->input('subject_content');
            $subject->image = $request->input('subject_image');
            $subject->promotional_price = $request->input('promotional_price_subject');
            $subject->updated_by = auth()->user()->email;
            $subject->status = $request->status;
            $subject->save();
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
    /**
     * Remove the specified resource from storage.
     */
    public function deleteSubject($id)
    {
        try {
            $subject = Subject::find($id);
            if ($subject == null) {
                return response()->json([
                    'error_message' => 'No subject found'
                ], 400);
            }
            $subject->update([
                'status' => 0
            ]);
            return response()->json([
                'result' => 'succes'
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
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

    public function getLatestSubjects(Request $request)
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('ADMIN_SUBJECT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $latestSubjects = Subject::orderByDesc('created_at')->take(5)->get();
            $result = [];
            foreach ($latestSubjects as $latestSubject) {
                $subjectId = $latestSubject->id;
                $subject = Subject::with('courses')->find($subjectId);
                $price = 0;
                $courses = $subject->courses;
                foreach ($courses as $course) {
                    $price += $course->price;
                    $price -= $course->promotional_price;
                }
                $price -= $subject->promotional_price;
                if ($price < 0) {
                    $price = 0;
                }
                $data = [
                    "image" => $latestSubject->image,
                    "name" => $latestSubject->name,
                    "price" => $price,
                ];
                array_push($result, $data);
            }
            return response()->json([
                'latest_subjects' => $result,
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }
}
