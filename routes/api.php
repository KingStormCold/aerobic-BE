<?php

use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\Client\CourseClientController;
use App\Http\Controllers\Api\Client\VideoClientController;
use App\Http\Controllers\Api\Client\TestClientController;
use App\Http\Controllers\Api\Client\AnswerClientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/change-pass', [AuthController::class, 'changePassWord']);
    Route::get('/forgot-password', [AuthController::class, 'forgotPass']);
    Route::post('/forgot-password', [AuthController::class, 'postForgotPass']);
    Route::get('/get-password', [AuthController::class, 'getPass']);
    Route::post('/get-password', [AuthController::class, 'postGetPass']);


    Route::get('/get-parent-categories', [CategoryController::class, 'getParentCategories']);
    Route::get('/get-categories', [CategoryController::class, 'getCategories']);
    Route::get('/category/{id}', [CategoryController::class, 'getCategory']);
    Route::post('/category', [CategoryController::class, 'insertCategory']);
    Route::put('/category/{id}', [CategoryController::class, 'updateCategory']);
    Route::delete('/category/{id}', [CategoryController::class, 'deleteCategory']);
    Route::get('/get-child-categories', [CategoryController::class, 'getChildCategories']);
    Route::get('/get-Menu', [CategoryController::class, 'getMenu']);

    Route::get('/get-subjects', [SubjectController::class, 'getSubjects']);
    Route::get('/get-subject/{id}', [SubjectController::class, 'getSubject']);
    Route::post('/subject', [SubjectController::class, 'insertSubject']);
    Route::put('/subject/{id}', [SubjectController::class, 'updateSubject']);
    Route::delete('/subject/{id}', [SubjectController::class, 'deleteSubject']);
    Route::get('/show-subject', [SubjectController::class, 'showSubject']);
    Route::get('/full-subjects', [SubjectController::class, 'fullSubjects']);

    Route::get('/get-course', [CourseController::class, 'getCourse']);
    Route::get('/courses/{id}', [CourseController::class, 'courses']);
    Route::get('/showcourse/{id}', [CourseController::class, 'showCourse']);
    Route::post('/insert-course', [CourseController::class, 'insertCourse']);
    Route::put('/course/{id}', [CourseController::class, 'updateCourse']);
    Route::delete('/course/{id}', [CourseController::class, 'deleteCourse']);
    Route::get('/show-course-name', [CourseController::class, 'showCourseName']);
    

    Route::get('/get-user', [UserController::class, 'getUser']);
    Route::get('/get-parent-users', [UserController::class, 'getParentUsers']);
    Route::get('/get-users', [UserController::class, 'getUsers']);
    Route::get('/user/{id}', [UserController::class, 'getUser']);
    Route::post('/user', [UserController::class, 'insertUser']);
    Route::put('/user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/user/{id}', [UserController::class, 'deleteUser']);
    Route::get('/get-roles', [UserController::class, 'getRoles']);

    Route::get('/get-video', [VideoController::class, 'getVideo']);
    Route::get('/videos/{id}', [VideoController::class, 'videos']);
    Route::get('/show-videos/{id}', [VideoController::class, 'showVideos']);
    Route::post('/insert-video', [VideoController::class, 'insertVideo']);
    Route::put('/video/{id}', [VideoController::class, 'updateVideo']);
    Route::delete('/video/{id}', [VideoController::class, 'deleteVideo']);
    Route::get('/show-video-name', [VideoController::class, 'showVideoName']);
    

    Route::get('/get-answers', [AnswerController::class, 'getAnswers']);
    Route::get('/get-answer', [AnswerController::class, 'getAnswer']);
    Route::post('/answer', [AnswerController::class, 'insertAnswer']);
    Route::put('/answer/{id}', [AnswerController::class, 'updateAnswer']);
    Route::delete('/answer/{id}', [AnswerController::class, 'deleteAnser']);
    

    Route::get('/get-payments', [PaymentController::class, 'getPayments']);
    Route::get('/get-details', [PaymentController::class, 'getDetail']);

    Route::get('/get-tests', [TestController::class, 'getTests']);
    Route::get('/test/{id}', [TestController::class, 'test']);
    Route::get('/show-tests/{id}', [TestController::class, 'showTests']);
    Route::post('/insert-test', [TestController::class, 'insertTest']);
    Route::put('/test/{id}', [TestController::class, 'updateTest']);
    Route::delete('/test/{id}', [TestController::class, 'deleteTest']);
    
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'client'
], function ($router) {
    Route::get('/get-fullcourses', [CourseClientController::class, 'fullCourses']);
    Route::get('/get-fullvideos', [VideoClientController::class, 'fullVideos']);
    Route::get('/get-fulltests', [TestClientController::class, 'fullTests']);
    Route::get('/get-fullanswers', [AnswerClientController::class, 'fullAnswers']);
});
