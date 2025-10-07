<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory; 
    protected $fillable = [
        'post_id',
        'user_id',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLike(): bool
    {
        return $this->type === 'like';
    }

    public function isDislike(): bool
    {
        return $this->type === 'dislike';
    }
}
