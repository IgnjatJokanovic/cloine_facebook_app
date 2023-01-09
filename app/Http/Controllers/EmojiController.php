<?php

namespace App\Http\Controllers;

use App\Models\Emoji;
use Illuminate\Http\Request;

class EmojiController extends Controller
{
    public function index()
    {
        $emotions = Emoji::all();
        return response()->json($emotions);
    }
}
