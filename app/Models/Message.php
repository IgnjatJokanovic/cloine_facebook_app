<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use HasFactory;

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
                    'm1.from',
                    'm1.to',
                    'm1.body',
                    'm1.created_at',
                    'users.firstName',
                    'users.lastName',
                    'images.src AS profile'
                );
    }
}
