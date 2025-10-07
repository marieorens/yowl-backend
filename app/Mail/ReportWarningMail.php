<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $post;
    public $reportCount;

    public function __construct(User $user, Post $post, $reportCount)
    {
        $this->user = $user;
        $this->post = $post;
        $this->reportCount = $reportCount;
    }

    public function build()
    {
        $subject = $this->reportCount == 5 
            ? 'URGENT: Votre compte a été désactivé suite aux signalements'
            : 'Avertissement: Votre post a été signalé plusieurs fois';

        return $this->view('emails.report-warning')
                    ->subject($subject)
                    ->with([
                        'userName' => $this->user->name,
                        'postTitle' => $this->post->title,
                        'reportCount' => $this->reportCount,
                        'isAccountDeactivated' => $this->reportCount >= 5
                    ]);
    }
}