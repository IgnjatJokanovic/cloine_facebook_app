<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'owner',
        'creator',
        'image_id',
        'emotion_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function emotion()
    {
        return $this->belongsTo(Emoji::class, 'emotion_id');
    }
}
