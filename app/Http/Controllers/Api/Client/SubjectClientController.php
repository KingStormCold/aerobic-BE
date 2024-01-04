<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SubjectClientController extends Controller
{
    public function fullSubjects($categoryId)
    {
        try {
            $subject = Subject::where('category_id', $categoryId)->orderByDesc('created_at')->first();
            $category = Category::find($categoryId);
            if (!$category) {
                return response()->json([
                    'message' => 'Không tìm thấy danh mục.'
                ], 400);
            }
            if (!$subject) {
                return response()->json([
                    'message' => 'Không tìm thấy môn học cho danh mục này.'
                ], 400);
            }
            return response()->json([
                'category' => $this->customfullSubject($subject),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }

    public function customfullSubject($subject)
    {
        $categoryId = $subject->category_id;
        $category = Category::find($categoryId);
    
        $categoryData = [
            "subject_id" => $subject->id,
            "subject_name" => $subject->name,
            "subject_content" => $subject->content,
            "subject_image" => $subject->image,
        ];
    
        return $categoryData;
    }

    public function GetFullSubjectClient (Request $request){
        $validator = Validator::make($request->all(), [
            'content_search' => 'required|max:255',
        ],[
            'content_search.required' => 'hãy nhập từ khóa tìm kiếm',
            'content_search.max' => 'chỉ nhập dưới 255 kí tự'
        ]);
        $content_search = $request->input('content_search');
        try {
            $subjects = Subject::select('id','name','created_at','image','promotional_price')->where("content","like","%".$content_search."%")->orderBy('created_at')->limit(3)->get();

            $result = [];

            foreach ($subjects as $subject) {
                $subjectData = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'image' => $subject->image,
                    'promotional_price' => $subject->promotional_price                
                ];
                $result[] = $subjectData;
            }
            return response()->json([
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
}
