<?php

namespace App\Models;

use App\Events\NewNotification;
use App\Events\NotificationRemoved;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'user_id',
        'creator',
        'post_id',
        'comment_id',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'creator', 'id');
    }


    public static function boot()
    {
        parent::boot();

        self::created(function($model){
           $model->load('user.profilePhoto.image');
           Log::debug("message");
           broadcast(new NewNotification($model))->toOthers();
        });

        self::deleted(function($model){
            Log::debug("DELETING");
            Log::debug($model);
            broadcast(new NotificationRemoved(
                $model->user_id,
                $model->id,
            ));
        });
    }
}
