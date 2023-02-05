<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmojiController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
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



Route::group(['middleware' => ['jwt']], function () {
    Route::prefix('post')->group(function(){
        Route::get('/', [PostController::class, 'index']);
        Route::post('/create', [PostController::class, 'create']);
        Route::post('/update', [PostController::class, 'update']);
    });

    Route::prefix('reaction')->group(function(){
        Route::post('/create', [ReactionController::class, 'create']);
    });
});
