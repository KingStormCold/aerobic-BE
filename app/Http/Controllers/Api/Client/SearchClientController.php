<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchClientController extends Controller
{
    public function searchClient(Request $request) {
        $validator = Validator::make($request->all(), [
            'content_search' => 'required|max:255',
        ],[
            'content_search.required' => 'hãy nhập từ khóa tìm kiếm',
            'content_search.max' => 'chỉ nhập dưới 255 kí tự'
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            foreach ($errors as $key => $error) {
                return response()->json([
                    'error_message' => $error
                ], 400);
            }
        }
    
        $content_search = $request->input('content_search');
        $results = Subject::select('id','content','name','image')->where("content","like","%".$content_search."%")->get();
        if($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy tên môn học phù hợp'
            ]);
        }
        return $results;
    }
    
}
