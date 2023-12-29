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

class FogotpasswordClientController extends Controller
{
    public function Forgotpassword (Request $request, $email){
        try {
            $validator = Validator::make($request->all(), [ 
                'email' => 'required|email' 
            ], [     
                'email.required' => 'email không được trống'           
            ]);
            $user = User::where('email', $email)->first();
            if(!$user){
                return response()->json([
                    'error_message' => 'Email không tồn tại'
                ], 400);
            } else {
                $resetUUid = Str::uuid()->toString();
                $user->uuid = $resetUUid;
                $result = $user->save();  
                if ($result) {
                    $resetUrl  = "http://localhost:9000/forgot-password?id=" . $resetUUid;
                    // Mail::send('email.view', ['resetUrl' => $resetUrl], function ($message) use ($email) {
                    //     $message->to($email)->subject('Đổi mật khẩu');
                    // });  
                    Mail::raw('Nội dung Email test. '.  $resetUrl, function ($message) use ($email) {
                        $message->to($email)->subject('Đổi mật khẩu');
                    }); 
                    return response()->json([
                        'result' => 'success'
                    ], 200);            
                }  
            }          
        } catch (Exception $e) { 
           return response()->json([
                'error_message' => $e->getMessage()
                ], 500);
        }
    }
}
