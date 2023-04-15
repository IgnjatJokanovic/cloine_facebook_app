<?php

namespace App\Models;

use App\Dto\FriendRequestDto;
use App\Events\FriendRequestCanceled;
use App\Events\FriendRequestSent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use function App\Providers\notifyFriendRequesCanceled;
use function App\Providers\notifyFriendRequestSent;
use function App\Providers\notifyNotificationRecieved;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'to',
        'from',
        'accepted',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'from', 'id');
    }

    public static function boot()
    {
        parent::boot();

        self::created(function($model){
           $model->load('user.profilePhoto.image');
           $requestDto = new FriendRequestDto(
            $model->from,
            $model->to,
            $model->user->firstName,
            $model->user->lastName,
            $model->user->profile_photo,
            false,
            false,
           );
           broadcast(new FriendRequestSent($requestDto))->toOthers();
        });

        self::deleting(function($model){
            if(!$model->accepted){
                broadcast(new FriendRequestCanceled($model->to, $model->from))->toOthers();
            }
        });
    }
}
