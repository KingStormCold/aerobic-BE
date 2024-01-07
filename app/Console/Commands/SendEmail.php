<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class SendEmail extends Command
{
    public function handle()
    {
        try {
            $payments = Payment::whereNotNull('users_id')->get();
            if ($payments->isNotEmpty()) {
                foreach ($payments as $payment) {
                    $user = User::find($payment->users_id);
                    if ($user !== null && $user->status === 1) {
                        $email = $user->email;
                        $result = Mail::raw('Remember to study.', function ($message) use ($email) {
                            $message->to($email)->subject('Remember to study today. https://earobic4og.xyz');
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
