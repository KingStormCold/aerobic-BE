<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
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
            "subjectName" => $subject->name,
            "subject_content" => $subject->content,
            "subject_image" => $subject->image,
        ];

        return $categoryData;
    }
}
