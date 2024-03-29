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
use App\Models\VideoUser;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentClientController extends Controller
{
    public function registerCourse(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'code' => 'Course_001',
                    'error_message' => 'You need to log in to enroll in the course.'
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'subject_id' => 'required',
                'subject_full' => 'required',
                'free' => 'required'
            ], []);

            $free = $request->input('free');
            $subjectFull = $request->input('subject_full');
            $subjectId = $request->input('subject_id');
            $subject = Subject::with('courses')->find($subjectId);
            if ($subjectFull === 1) {
                if ($subject === null) {
                    return response()->json([
                        'error_message' => 'No subject found'
                    ], 400);
                } else {
                    $courses = $subject->courses;
                    $price = 0;
                    $courseId = [];
                    $priceFull = 0;


                    foreach ($courses as $course) {
                        if ($course->status === 0) {
                            $price += 0;
                            $courseId[] = $course->id;
                        } else {
                            $price += $course->price;
                            $price -= $course->promotional_price;
                            $courseId[] = $course->id;
                        }
                    }
                    $price -= $subject->promotional_price;
                    $user = auth()->user();

                    foreach ($courseId as $id) {
                        $payments = Payment::where('courses_id', $id)->where('users_id', Auth::id())->get();
                        foreach ($payments as $payment) {
                            $priceFull += $payment->price;
                        }
                    }

                    if ($priceFull !== 0) {
                        return response()->json([
                            'error_message' => 'You have purchased this course or subject before.'
                        ], 400);
                    }
                    if ($user->money < $price) {
                        return response()->json([
                            'error_message' => 'You dont have enough money in your account. Please, go to recharge page'
                        ], 400);
                    } else {
                        $user->money -= $price;
                        $user->save();
                        foreach ($courses as $course) {
                            if ($course->level === 1 && $course->status === 1 &&  Payment::where('users_id', $user->id)->where('courses_id', $courseId)->exists()) {
                                Payment::where('users_id', $user->id)->where('courses_id', $course->id)->update([
                                    'price' => $course->price - $course->promotional_price,
                                ]);
                            } else if ($course->level === 1 && $course->status === 1 && !Payment::where('users_id', $user->id)->where('courses_id', $courseId)->exists()) {
                                Payment::create([
                                    'price' => $course->price - $course->promotional_price,
                                    'subject_full' => $subjectFull,
                                    'users_id' => $user->id,
                                    'courses_id' => $course->id
                                ]);
                            } else if ($course->status === 1) {
                                Payment::create([
                                    'price' => $course->price - $course->promotional_price,
                                    'subject_full' => $subjectFull,
                                    'users_id' => $user->id,
                                    'courses_id' => $course->id
                                ]);
                            }
                        }
                        return response()->json([
                            'result' => 'Successful'
                        ], 200);
                    }
                }
            } else {
                $courseId = $request->input('course_id');
                $courses = Course::find($courseId);
                $user = auth()->user();
                $price = $courses->price - $courses->promotional_price;
                $payment = Payment::where('users_id', $user->id)->where('courses_id', $courseId)->first();

                if ($free === 1) {
                    if ($payment !== null) {
                        if ($payment->price === 0 || $payment->price !== 0) {
                            return response()->json([
                                'error_message' => 'You have purchased this course or subject before.'
                            ], 400);
                        }
                    }
                    Payment::create([
                        'price' => 0,
                        'subject_full' => $subjectFull,
                        'users_id' => $user->id,
                        'courses_id' => $courses->id
                    ]);
                    return response()->json([
                        'result' => 'Successful'
                    ], 200);
                } else {
                    if ($user->money < $price) {
                        return response()->json([
                            'error_message' => 'You dont have enough money in your account'
                        ], 400);
                    }
                    if ($payment !== null) {
                        if ($payment->price !== 0) {
                            return response()->json([
                                'error_message' => 'You have purchased this course or subject before.'
                            ], 400);
                        }
                        $user->money -= $price;
                        $user->save();
                        $payment->update([
                            'price' => $price,
                        ]);
                        return response()->json([
                            'result' => 'Successful'
                        ], 200);
                    } else {
                        $user->money -= $price;
                        $user->save();
                        Payment::create([
                            'price' => $price,
                            'subject_full' => $subjectFull,
                            'users_id' => $user->id,
                            'courses_id' => $courses->id
                        ]);
                        return response()->json([
                            'result' => 'Successful'
                        ], 200);
                    }
                }
            }
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
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
                    'error_message' => 'You need to be logged in'
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
                            $totalVideos = Video::where('course_id', $courseDetail['id'])->get();
                            $totalFinishVideo = 0;
                            foreach ($totalVideos as $videoDetail) {
                                $videoUser = VideoUser::where('users_id', Auth::id())->where('videos_id', $videoDetail->id)->where('finished', 1)->first();
                                if ($videoUser !== null) {
                                    $totalFinishVideo++;
                                }
                            }
                            $totalVideo = count($totalVideos);
                            $data = [
                                "id" => $courseDetail['id'],
                                "name" => $courseDetail['name'],
                                "description" => $courseDetail['description'],
                                "image" => $courseDetail['image'],
                                "created_date" => $courseDetail['created_date'],
                                "progress_course" => $totalFinishVideo !== 0 ? (($totalFinishVideo / $totalVideo) * 100) : 0,
                                "status" => ($totalVideo === $totalFinishVideo),
                                "total_video" => $totalVideo,
                                "total_finish_video" => $totalFinishVideo
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
                'error_message' =>  'You havent purchased a course yet.'
            ], 400);
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => $e
            ]);
        }
    }

    public function getPayments()
    {
        try {
            $authController = new AuthController();
            $isAuthorization = $authController->isAuthorization('USER');

            if (!$isAuthorization) {
                return response()->json([
                    'code' => 'SUB_001',
                    'error_message' => 'You have no rights.'
                ], 401);
            }
            $payments = Payment::where('users_id', Auth::id())->orderBy('created_at')->paginate(10);
            return response()->json([
                'payments' => $this->customPaymentDetail($payments->items()),
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

    public function customPaymentDetail($payments)
    {
        $result = [];
        foreach ($payments as $payment) {
            $user = User::find($payment->users_id);
            $course = Course::find($payment->courses_id);
            $subject = Subject::find($course->subject_id);
            $courseData = [
                "subjectName" => $subject->name,
                "courseName" => $course->name,
                "price" => $payment->price,
                "created_at" => $payment->created_at,
            ];
            $result[] = $courseData;
        }
        return $result;
    }
}
