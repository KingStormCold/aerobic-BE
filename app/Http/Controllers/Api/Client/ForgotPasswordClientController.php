<?php

namespace App\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class ForgotPasswordClientController extends Controller
{
    public function forgotPassword(Request $request, $email)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ], [
                'email.required' => 'Email cant be blank'
            ]);
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'error_message' => 'Email doesnt exist'
                ], 400);
            } else {
                $resetUUid = Str::uuid()->toString();
                $user->uuid = $resetUUid;
                $result = $user->save();
                if ($result) {
                    $resetUrl  = "http://localhost:9000/forgot-password?id=" . $resetUUid;
                    Mail::raw('Change password. ' .  $resetUrl, function ($message) use ($email) {
                        $message->to($email)->subject('Change password');
                    });
                    return response()->json([
                        'result' => 'success'
                    ], 200);
                }
            }
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => $e->getMessage()
            ], 500);
        }
    }
    public function checkUuid($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if ($user !== null) {
                if ($user->uuid !== null) {
                    $user->uuid = "";
                    $user->save();
                    return response()->json([
                        'result' => 'success'
                    ], 200);
                }
            } else {
                return response()->json([
                    'result' => 'No uuid found'
                ], 400);
            }
        } catch (Exception $e) {
            Log::info('[Exception] ' + $e);
            return response()->json([
                'error_message' => $e->getMessage()
            ], 500);
        }
    }
}
