<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'src',
    ];


    public static function boot()
    {
        parent::boot();
        static::deleted(function($image)
        {
            \File::delete($image->src);
        });
    }
}
