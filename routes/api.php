<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\PaymentController;
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

    Route::get('/get-parent-categories', [CategoryController::class, 'getParentCategories']);
    Route::get('/get-categories', [CategoryController::class, 'getCategories']);
    Route::get('/category/{id}', [CategoryController::class, 'getCategory']);
    Route::post('/category', [CategoryController::class, 'insertCategory']);
    Route::put('/category/{id}', [CategoryController::class, 'updateCategory']);
    Route::delete('/category/{id}', [CategoryController::class, 'deleteCategory']);
    Route::get('/get-child-categories', [CategoryController::class, 'getChildCategories']);

    Route::get('/get-subjects', [SubjectController::class, 'getSubjects']);
    Route::get('/get-subject/{id}', [SubjectController::class, 'getSubject']);
    Route::post('/subject', [SubjectController::class, 'insertSubject']);
    Route::put('/subject/{id}', [SubjectController::class, 'updateSubject']);
    Route::delete('/subject/{id}', [SubjectController::class, 'deleteSubject']);

    Route::get('/get-parent-course', [CourseController::class, 'getParentCourse']);
    Route::post('/course', [CourseController::class, 'insertCourse']);
    Route::put('/course/{id}', [CourseController::class, 'updateCourse']);
    Route::delete('/course/{id}', [CourseController::class, 'deleteCourse']);

    Route::get('/get-user', [UserController::class, 'getUser']);
    Route::post('/user', [UserController::class, 'insertUser']);
    Route::put('/user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/user/{id}', [UserController::class, 'deleteUser']);

    Route::get('/get-answers', [AnswerController::class, 'getAnswers']);
    Route::get('/get-answer', [AnswerController::class, 'getAnswer']);
    Route::post('/answer', [AnswerController::class, 'insertAnswer']);
    Route::put('/answer/{id}', [AnswerController::class, 'updateAnswer']);
    Route::delete('/answer/{id}', [AnswerController::class, 'deleteAnser']);
    
    Route::get('/get-payments', [PaymentController::class, 'getPayments']);
    Route::get('/get-details', [PaymentController::class, 'getDetail']);
});
