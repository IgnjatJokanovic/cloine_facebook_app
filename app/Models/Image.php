<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'src',
    ];


    public static function boot()
    {
        parent::boot();

        static::deleting(function($image)
        {
            Log::debug('uso');
            \File::delete(public_path() . $image->src);
        });
    }
}
