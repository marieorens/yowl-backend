<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->verificationUrl = config('app.frontend_url') . '/verify-email?token=' . $user->email_verification_token;
    }

    public function build()
    {
        return $this->view('emails.email-verification')
                    ->subject('Confirmez votre adresse email - ' . config('app.name'))
                    ->with([
                        'userName' => $this->user->name,
                        'verificationUrl' => $this->verificationUrl
                    ]);
    }
}