<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subject;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;

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
            $isAuthorization = $authController->isAuthorization('ADMIN_PAYMENT');
            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $payments = Payment::orderByDesc('courses_id')->paginate(10);

            return response()->json([
                'payments' => $this->custompayments($payments->items()),
                'totalPage' => $payments->lastPage(),
                'pageNum' => $payments->currentPage(),
            ], 200);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => 'System error. Please try again later'
            ], 500);
        }
    }
    public function custompayments($payments)
    {
        $result = [];
        foreach ($payments as $payment) {
            if ($payment->users_id !== "") {
                $user = User::find($payment->users_id);
            }

            if ($payment->courses_id !== "") {
                $course = Course::find($payment->courses_id);
            }

            $subject = Subject::find($course->subject_id);
            $data = [
                "id" => $payment->id,
                "email" => $user->email,
                "course_name" => $course->name,
                "subject_name" => $subject->name,
                "price" => $payment->price,
                "created_at" => $payment->created_at,
            ];
            array_push($result, $data);
        }
        return $result;
    }
}
