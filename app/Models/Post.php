<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;

class Post extends Model
{
    use HasFactory;
    // protected $hidden = ['pivot'];

    protected $fillable = [
        'body',
        'owner',
        'creator',
        'image_id',
        'emotion_id',
    ];

    protected $appends = ['currentUserReaction'];

    public function getCurrentUserReactionAttribute($value)
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = $payload->get('id');
            return  Reaction::with('emotion')
                        ->where([
                            'user_id' => $userId,
                            'post_id' => $this->id
                        ])
                        ->first();
        } catch (Exception $e) {

        }

        return null;
    }

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

    public function taged()
    {
        return $this->belongsToMany(User::class, 'user_posts', 'post_id', 'user_id');
    }

    public function reactions()
    {
        return $this->belongsToMany(Emoji::class, 'reactions', 'post_id', 'reaction_id');
    }

    public function distinctReactions()
    {
        return  $this->reactions()->select(
            'emoji.*',
            DB::raw('count(reactions.reaction_id) as reaction_count')
            )
            ->groupBy('emoji.id', 'reactions.post_id', 'reactions.reaction_id');

    }

    public function currentUserReaction(){
         try {
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = $payload->get('id');
            return  Reaction::with('emotion')
                        ->where([
                            'user_id' => $userId,
                            'post_id' => $this->id
                        ])
                        ->first();
        } catch (Exception $e) {

        }

        return null;
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function($post){
            Log::debug('uso u post');
           $post->image()->delete();
        });
    }
}
