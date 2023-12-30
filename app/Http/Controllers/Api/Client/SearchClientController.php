<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subject;
use Exception;

class SearchClientController extends Controller
{
    public function searchClient($subjectContent) {
        $results = Subject::where("content","like","%".$subjectContent."%")->get();
        if($results->isEmpty()) {
            return response()->json([
                'message' => 'Không tìm thấy tên môn học phù hợp'
            ]);
        }
        return $results;
    }
}
