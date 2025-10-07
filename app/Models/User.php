<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_pic',
        'birthday',
        'is_active',
        'email_verified_at',
        'email_verification_token',
        'password_reset_token',
        'password_reset_expires',
    ];
    /**
     * Relations
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_user_id');
    }

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive()
    {
        return $this->is_active ?? false;
    }

    /**
     * Vérifier si l'email est vérifié
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Générer un token de vérification d'email
     */
    public function generateEmailVerificationToken()
    {
        $this->email_verification_token = \Str::random(64);
        $this->save();
        return $this->email_verification_token;
    }

    /**
     * Générer un token de réinitialisation de mot de passe
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = \Str::random(64);
        $this->password_reset_expires = now()->addHour(); 
        $this->save();
        return $this->password_reset_token;
    }

    /**
     * Vérifier si le token de reset est valide
     */
    public function isValidPasswordResetToken($token)
    {
        return $this->password_reset_token === $token && 
               $this->password_reset_expires && 
               $this->password_reset_expires->isFuture();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_reset_expires' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
