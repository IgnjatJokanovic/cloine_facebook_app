<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'user_id',
        'post_id',
        'comment_id',
    ];

    protected $appends = [
        'repliesCount'
    ];

    public function getRepliesCountAttribute($value)
    {
        if($this->comment_id === null){
            return DB::table('comments')
                     ->where('comment_id', $this->id)
                     ->count();
        }

        return 0;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'comment_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(self::class, 'comment_id', 'id');
    }



    public static function boot()
    {
        parent::boot();
        static::deleting(function($model){
           $model->notifications()?->delete();
           $model->comments()?->delete();
        });
    }



}
