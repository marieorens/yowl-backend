<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ReportWarningMail;
use App\Models\User;
use App\Models\Post;

class MailService
{
    /**
     * Envoyer un email d'avertissement pour signalement
     */
    public function sendReportWarning(User $user, Post $post, int $reportCount)
    {
        try {
            Mail::to($user->email)->send(new ReportWarningMail($user, $post, $reportCount));
            
            Log::info("Email d'avertissement envoyé avec succès", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'post_id' => $post->id,
                'report_count' => $reportCount
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi d'email", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'post_id' => $post->id,
                'report_count' => $reportCount,
                'error' => $e->getMessage()
            ]);

            return $this->fallbackNotification($user, $post, $reportCount);
        }
    }

    /**
     * Système de fallback si l'email échoue
     */
    private function fallbackNotification(User $user, Post $post, int $reportCount)
    {
        Log::critical("NOTIFICATION CRITIQUE - Email échoué", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'post_id' => $post->id,
            'post_title' => $post->title,
            'report_count' => $reportCount,
            'action_required' => $reportCount >= 5 ? 'Compte désactivé' : 'Avertissement'
        ]);

        \DB::table('failed_notifications')->insert([
            'user_id' => $user->id,
            'type' => 'report_warning',
            'data' => json_encode([
                'post_id' => $post->id,
                'report_count' => $reportCount
            ]),
            'created_at' => now()
        ]);

        return false;
    }

    /**
     * Envoyer un email de vérification d'email
     */
    public function sendEmailVerification(User $user)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\EmailVerificationMail($user));
            
            Log::info("Email de vérification envoyé avec succès", [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email de vérification", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function sendPasswordReset(User $user)
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordResetMail($user));
            
            Log::info("Email de réinitialisation envoyé avec succès", [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email de réinitialisation", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Tester la configuration email
     */
    public function testEmailConfiguration()
    {
        try {
            Mail::raw('Email de test depuis Makers Community', function ($message) {
                $message->to(config('mail.from.address'))
                        ->subject('Test de configuration email');
            });
            return true;
        } catch (\Exception $e) {
            Log::error("Test email échoué: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un email admin pour notifications importantes
     */
    public function notifyAdmins($subject, $message, $data = [])
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            try {
                Mail::raw($message, function ($mail) use ($admin, $subject) {
                    $mail->to($admin->email)
                         ->subject('[ADMIN] ' . $subject);
                });
            } catch (\Exception $e) {
                Log::error("Erreur notification admin", [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}