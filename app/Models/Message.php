<?php

namespace App\Models;

use App\Dto\MessageDto;
use App\Dto\MessageNotificationDto;
use App\Events\MessageDeleted;
use App\Events\MessageRecieved;
use App\Events\MessageUpdated;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'from',
        'to',
        'opened'
    ];

    // protected $appends = ['isRead'];


    public function getisReadAttribute($value)
    {
        /** @var User $user */
        $user = auth()->user();
        $participant = $this->to !== $user->id ? $this->to : $this->from;

        return self::where([
                    'to' => $user->id,
                    'from' => $participant,
                    'opened' => false,
                ])->count() > 0;
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'from', 'id');
    }

    public static function latestMessages(int $userId)
    {
        return DB::table('messages AS m1')
                ->where(function($q) use($userId){
                    $q->where('m1.to', $userId)
                        ->orWHere('m1.from', $userId);
                })
                ->where('m1.created_at', function($q){
                    $q->select(DB::raw('MAX(m3.created_at)'))
                        ->from('messages AS m3')
                        ->where(function($q){
                            $q->where('m3.from', DB::raw('m1.from'))
                                ->where('m3.to', DB::raw('m1.to'));
                        })
                        ->orWhere(function($q){
                            $q->where('m3.from', DB::raw('m1.to'))
                                ->where('m3.to', DB::raw('m1.from'));
                        });
                })
                ->join('users', function ($join) use ($userId) {
                    $join->on(DB::raw('CASE m1.to WHEN '.$userId.' THEN m1.from ELSE m1.to END'), '=', 'users.id');
                })
                ->leftJoin('posts', 'users.profile', '=', 'posts.id')
                ->leftJoin('images', 'posts.image_id', '=', 'images.id')
                ->orderBy('m1.opened', 'ASC')
                ->select(
                    'users.id',
                    'users.firstName',
                    'users.lastName',
                    'images.src AS profile',
                    'm1.id as messageId',
                    'm1.from',
                    'm1.to',
                    'm1.body',
                    'm1.created_at',
                    DB::raw("(CASE WHEN (SELECT COUNT(*) FROM messages m3 WHERE m3.from = (CASE m1.to WHEN $userId THEN m1.from ELSE m1.to END) AND m3.to = $userId AND opened = false) > 0 THEN 0 ELSE 1 END) AS opened")
                );
    }

    public static function search(int $id, string $param)
    {
        return DB::table('messages')
                    ->join('users', function ($join) use ($id) {
                        $join->on(DB::raw('CASE messages.to WHEN '.$id.' THEN messages.from ELSE messages.to END'), '=', 'users.id');
                    })
                    ->leftJoin('posts', 'users.profile', '=', 'posts.id')
                    ->leftJoin('images', 'posts.image_id', '=', 'images.id')
                    ->where(function($q) use ($id){
                        $q->where('messages.from', $id)
                            ->orWhere('messages.to', $id);
                    })
                    ->where(function($q) use($param){
                        $q->where('messages.body', 'ILIKE', "%$param%")
                            ->orWhere('users.firstName', 'ILIKE', "%$param%")
                            ->orWhere('users.lastName', "ILIKE", "%$param%");
                    })
                    ->orderBy('messages.created_at', 'DESC')
                    ->select(
                        'users.id',
                        'users.firstName',
                        'users.lastName',
                        'images.src AS profile',
                        'messages.from',
                        'messages.to',
                        'messages.body',
                        'messages.created_at',
                        'messages.opened',
                    );
    }

    public static function latest(int $id, int $userId)
    {

        return DB::table('messages AS m1')
                ->where(function($q) use($id, $userId){
                    $q->where('from', $id)
                        ->where('to', $userId);
                })
                ->orWhere(function($q) use($id, $userId){
                    $q->where('to', $id)
                        ->where('from', $userId);
                })
                ->join('users', function ($join) use ($userId) {
                    $join->on(DB::raw('CASE m1.to WHEN '.$userId.' THEN m1.from ELSE m1.to END'), '=', 'users.id');
                })
                ->leftJoin('posts', 'users.profile', '=', 'posts.id')
                ->leftJoin('images', 'posts.image_id', '=', 'images.id')
                ->orderBy('m1.created_at', 'DESC')
                ->select(
                    'users.id',
                    'users.firstName',
                    'users.lastName',
                    'images.src AS profile',
                    'm1.id as messageId',
                    'm1.from',
                    'm1.to',
                    'm1.body',
                    'm1.created_at',
                    DB::raw("(CASE WHEN (SELECT COUNT(*) FROM messages m3 WHERE m3.from = (CASE m1.to WHEN $userId THEN m1.from ELSE m1.to END) AND m3.to = $userId AND opened = false) > 0 THEN 0 ELSE 1 END) AS opened")
                )
                ->first();
    }

    public static function boot()
    {
        parent::boot();

        self::created(function($message){
            $message->load('user.profilePhoto.image');

            $messageDto = new MessageDto(
                $message->id,
                $message->user->firstName,
                $message->user->lastName,
                $message->from,
                $message->to,
                $message->user?->profilePhoto?->image?->src,
                $message->body,
                $message->created_at,
                false,
            );

            $notification = new MessageNotificationDto(
                $message->from,
                $message->user->firstName,
                $message->user->lastName,
                $message->user?->profilePhoto?->image?->src,
                $message->id,
                $message->from,
                $message->to,
                $message->body,
                $message->created_at,
                $message->isRead,
            );

            broadcast(new MessageRecieved(
                $messageDto,
                $notification
            ))->toOthers();
        });

        self::updated(function($model){
            $originalModel = $model->getOriginal();
            if($originalModel['opened'] != $model->opened){
                $id = $model->from;
            }else{
                $id = $model->to;
            }
            broadcast(new MessageUpdated($model, $id));
        });

        self::deleting(function($model){
            broadcast(new MessageDeleted($model));
        });
    }
}
