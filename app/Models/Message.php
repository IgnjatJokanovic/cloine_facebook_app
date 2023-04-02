<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'from',
        'to',
    ];

    public static function latestMessages(int $userId)
    {
        return DB::table('messages AS m1')
                ->leftjoin('messages AS m2', function($join) {
                    $join->on('m1.from', '=', 'm2.to');
                    $join->on('m1.id', '<', 'm2.id');
                })
                ->whereNull('m2.id')
                ->where(function($q) use($userId){
                    $q->where('m1.to', $userId)
                        ->orWHere('m1.from', $userId);
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
                    DB::raw("(CASE WHEN (SELECT COUNT(*) FROM messages m3 WHERE m3.from = m1.from AND m3.to = m1.to AND opened = false) > 0 THEN 0 ELSE 1 END) AS opened")
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
}
