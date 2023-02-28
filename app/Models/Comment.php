<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

}
