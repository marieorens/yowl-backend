<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->resetUrl = config('app.frontend_url') . '/reset-password?token=' . $user->password_reset_token;
    }

    public function build()
    {
        return $this->view('emails.password-reset')
                    ->subject('Réinitialisation de votre mot de passe - ' . config('app.name'))
                    ->with([
                        'userName' => $this->user->name,
                        'resetUrl' => $this->resetUrl
                    ]);
    }
}