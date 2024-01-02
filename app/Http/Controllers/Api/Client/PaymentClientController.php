<?php

namespace App\Http\Controllers\Api\Client;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentClientController extends Controller
{
    public function registerCourse(Request $request)
    {
        try {
            // Kiểm tra đăng nhập
            if (!Auth::check()) {
                return response()->json([
                    'code' => 'Course_001',
                    'message' => 'Bạn cần đăng nhập để đăng ký khóa học.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'subject_id' => 'required',
                'subject_full' => 'required'
            ], [
            ]);               
            // Lấy ID của người dùng đã đăng nhập
            $subjectFull = $request->input('subject_full');
            if ($subjectFull === 1) {
                $subjectId = $request->input('subject_id');
                $subject = Subject::with('courses')->find($subjectId);               
                if ($subject === null) {
                    return response()->json([
                        'error_message' => 'Không tìm thấy môn học'
                    ], 400);
                } else {
                    $courses = $subject->courses;
                    $price = 0;
                    $courseId = [];
                    foreach ($courses as $course) {
                        $price += $course->price; // $price = price + $course->price
                        $price -= $course->promotional_price; 
                        $courseId[] = $course->id;
                    }           
                    $price-= $subject->promotional_price; 
                    $user = auth()->user();
                    $payments = Payment::where('users_id', $user->id)->where('courses_id', $courseId) ->exists();
                    if ($payments) {
                        return response()->json([
                            'error_message' => 'Bạn đã mua khóa học và môn học này trước đó.'
                        ], 400);
                    }   
                    if ($user -> money < $price) {
                        return response()->json([
                            'error_message' => 'bạn không đủ tiền trong tài khoản'
                        ], 400);
                    } else {                    
                        $user->money -= $price;
                        $user->save();
                        foreach ($courses as $course) {
                            Payment::create([
                                'price' => $price,
                                'subject_full' => $subjectFull,
                                'users_id' => $user->id,
                                'courses_id' => $course->id
                            ]);
                        }
                        return response()->json([
                            'result' => 'success'
                        ], 200);
                    }
                }
            } else {
                $courseId = $request->input('course_id');
                $course = Course::find($courseId);          
                if ($course === null) {
                    return response()->json([
                        'error_message' => 'Không tìm thấy khóa học'
                    ], 400);
                } else {
                    $price = 0;
                    $price += $course->price;
                    $price -= $course->promotional_price;
                    $user = auth()->user();
                    $payments = Payment::where('users_id', $user->id)->where('courses_id', $course->id) ->exists();
                    if ($payments) {
                        return response()->json([
                            'error_message' => 'Bạn đã mua khóa học và môn học này trước đó.'
                        ], 400);
                    }
                    if ($user->money < $price) {
                        return response()->json([
                            'error_message' => 'bạn không đủ tiền trong tài khoản'
                        ], 400);
                    } else {
                        $user->money -= $price;
                        $user->save();
                        Payment::create([
                            'price' => $price,
                            'subject_full' => $subjectFull,
                            'users_id' => $user->id,
                            'courses_id' => $course->id
                        ]);
                        return response()->json([
                            'result' => 'success'
                        ], 200);
                    }
                }
            }          
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ], 500);
        }
    }

    public function paymentSubject()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'error_message' => 'ban can dang nhap'
                ], 401);
            }
            $userId = Auth::id();
            $payments = Payment::where('users_id', $userId)->get();
            if (!$payments->isEmpty()) {
                $payment_subject = [];
                foreach ($payments as $payment) {
                    $course = Course::with('subject')->find($payment->courses_id);
                    if ($course) {
                        $payment_subject[] = [
                            "id" => $course->id,
                            "name" => $course->name,
                            "description" =>  $course->description,
                            "subject_id" => $course->subject->id,
                            "subject_name" => $course->subject->name,
                            "image" => $course->subject->image
                        ];
                    }
                }
                return response()->json([
                    'payment_subjects' =>  $payment_subject,
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'error_message' => $e
            ]);
        }
    }
}
