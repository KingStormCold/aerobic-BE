<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendEmail extends Command
{
    protected $signature = 'send:email';

    protected $description = ' Gửi Email nhắc nhở ';

    public function handle()
    {
        try {
            $payments = Payment::whereNotNull('users_id')->get();
            if($payments ->isNotEmpty()){
                foreach ($payments as $payment) {
                    $user = User::find($payment->users_id);
                    if ($user) {
                        $email = $user->email;
                        $result = Mail::raw('Bạn nhớ học nhé.', function ($message) use ($email) {
                            $message->to($email)->subject('Hôm nay bạn nhớ học nhé');
                        });
                        if ($result) {
                            $this->info("Email sent successfully to {$email}.");
                            Log::info(' send sussesfully ');
                        } else {
                            $this->error("Failed to send email to {$email}.");
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
        }
    }
}
