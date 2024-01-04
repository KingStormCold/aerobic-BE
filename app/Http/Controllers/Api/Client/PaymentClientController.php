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
use App\Models\Video;
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
            ], []);
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
                    $price -= $subject->promotional_price;
                    $user = auth()->user();
                    $payments = Payment::where('users_id', $user->id)->where('courses_id', $courseId)->exists();
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
                    $payments = Payment::where('users_id', $user->id)->where('courses_id', $course->id)->exists();
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
        // try {
        if (!Auth::check()) {
            return response()->json([
                'error_message' => 'bạn cần phải đăng nhập'
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
                        "image" => $course->subject->image,
                        "created_date" => $payment->created_at
                    ];
                }
            }
            $result = [];
            $arraySubjectId = [];
            foreach ($payment_subject as $paymentSubject) {
                if (!array_keys($arraySubjectId, $paymentSubject['subject_id'])) {
                    $result[] = [
                        "subject_id" => $paymentSubject['subject_id'],
                        "subject_name" => $paymentSubject['subject_name'],
                        "courses" => []
                    ];
                }
                array_push($arraySubjectId, $paymentSubject['subject_id']);
            }
            foreach ($payment_subject as $courseDetail) {
                foreach ($result as $key => $item) {
                    if ($item['subject_id'] === $courseDetail['subject_id']) {
                        $totalVideo = Video::where('course_id', $courseDetail['id'])->count();
                        $totalFinishVideo = Video::where('course_id', $courseDetail['id'])->where('finished', 1)->count();
                        $data = [
                            "id" => $courseDetail['id'],
                            "name" => $courseDetail['name'],
                            "description" => $courseDetail['description'],
                            "image" => $courseDetail['image'],
                            "created_date" => $courseDetail['created_date'],
                            "progress_course" => (($totalFinishVideo / $totalVideo) * 100),
                            "status" => ($totalVideo === $totalFinishVideo)
                        ];
                        $ddd = $item['courses'];
                        array_push($ddd, $data);
                        $item['courses'] = $ddd;
                    }
                    $result[$key] = $item;
                }
            }
            return response()->json([
                'payment_subjects' =>  $result,
            ], 200);
        }
        return response()->json([
            'error_message' =>  'Bạn chưa mua khóa học nào cả.'
        ], 400);
        // } catch (Exception $e) {
        //     return response()->json([
        //         'error_message' => $e
        //     ]);
        // }
    }

    public function getPayments()
   {
       try {
           $authController = new AuthController();
           $isAuthorization = $authController->isAuthorization('USER');

           if (!$isAuthorization) {
               return response()->json([
                   'code' => 'SUB_001',
                   'message' => 'Bạn không có quyền.'
               ], 401);
           }


           $payments = Payment::where('users_id', Auth::id())->orderByDesc('created_at')->paginate(10);
           return response()->json([
               'payments' => $this->customPaymentDetail($payments->items()),
               'totalPage' => $payments->lastPage(),
               'pageNum' => $payments->currentPage(),
           ], 200);
            } catch (Exception $e) {
           return response()->json([
               'error_message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
           ], 500);
        }
    }

   public function customPaymentDetail($payments)
    {
    $result = []; 

    foreach ($payments as $payment) {
        $user = User::find($payment->users_id);
        $course = Course::find($payment->courses_id);

        $courseData = [
            "name" => $course->name,
            "price" => $course->price,
            "created_at" => $payment->created_at,
        ];

        $result[] = $courseData; 
    }

    return $result; 
    }

}
