<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Exception;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
     public function getPayments()
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
             $payments = Payment::orderByDesc('courses_id')->paginate(10);
             
             return response()->json([
                 'payments' => $this->custompayments($payments->items()),
                 // 'subjects' =>$subjects->items(),
                 'totalPage' => $payments->lastPage(),
                 'pageNum' => $payments->currentPage(),
             ], 200);
         } catch (Exception $e) {
             
             return response()->json([
                 'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
             ], 500);
         }
     }
     public function custompayments($payments)
    {
        $result = [];
        foreach ($payments as $payment) {
            $Pay = "";
            if ($payment->users_id !== "" ) {
                $user = User::find($payment->users_id);

                $userData = [
                    "email" => $user->email,                     
                ];
            }
            $Course = "";
            if ($payment->courses_id !== "" ) {
                $course = Course::find($payment->courses_id);
    
                $courseData = [
                    "name" => $course->name

                 
                ];     
            } 

            $data = [
                "id" => $payment->id,
                "user_data" => $userData,
                "course_data" => $courseData,
                "subject_full" =>$payment->subject_full,
                "price" => $payment->price,
                "created_by" => $payment->created_by,
                "updated_by" => $payment->updated_by,
                "created_at"=>$payment->created_at,
                "updated_at"=>$payment->updated_at,
                
            ];
            array_push($result, $data);
        }
        return $result;
    }





    /**
     * Store a newly created resource in storage.
     */
    public function getDetail()
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
            $payments = Payment::orderByDesc('courses_id')->paginate(10);
            
            return response()->json([
                'payments' => $this->customdetail($payments->items()),
                'totalPage' => $payments->lastPage(),
                'pageNum' => $payments->currentPage(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
            ], 500);
        }
    }
    public function customdetail($payments)
   {
       $result = [];
       foreach ($payments as $payment) {      
           if ($payment->users_id !== "" ) {
               $user = User::find($payment->users_id);
               $userData = [
                   "email" => $user->email,                     
               ];
           }  
           if ($payment->courses_id !== "" ) {
               $course = Course::find($payment->courses_id);
               $courseData = [
                   "name" => $course->name,
                   "description" => $course->description,
                   "level" => $course->level,
                   "price" => $course->price,
                   "promotional_price" => $course->promotional_price,
               ];     
           } 

           $data = [
               "id" => $payment->id,
               "user_data" => $userData,
               "course_data" => $courseData,
               "created_by" => $payment->created_by,
               "updated_by" => $payment->updated_by,
               "created_at"=>$payment->created_at,
               "updated_at"=>$payment->updated_at,
               
           ];
           array_push($result, $data);
       }
       return $result;
   }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}