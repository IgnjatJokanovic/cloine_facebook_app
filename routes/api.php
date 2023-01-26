<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmojiController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function(){
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/emojiList', [EmojiController::class, 'index']);

Route::prefix('user')->group(function(){
    Route::post('/create', [UserController::class, 'create']);
});

Route::prefix('post')->group(function(){
    Route::get('/{id}', [PostController::class, 'show']);
});

Route::get('/kita',  [PostController::class, 'kita']);



Route::group(['middleware' => ['jwt']], function () {
    Route::prefix('post')->group(function(){
        Route::post('/create', [PostController::class, 'create']);
        Route::post('/update', [PostController::class, 'update']);
    });
});
