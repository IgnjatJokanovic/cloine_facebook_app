<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmojiController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\FriendController;
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
    Route::get('/show/{id}', [UserController::class, 'show']);
    Route::post('/create', [UserController::class, 'create']);
});

Route::prefix('post')->group(function(){
    Route::get('/userRelated/photos/{id}/{take?}', [PostController::class, 'userRelatedPhotos']);
    Route::get('/userRelated/{id}', [PostController::class, 'userRelated']);
    Route::get('/show/{id}', [PostController::class, 'show']);
});

Route::prefix('friend')->group(function(){
    Route::get('/userFriends/{id}/{take?}', [FriendController::class, 'userFriends']);
});

Route::prefix('comment')->group(function(){
    Route::get('/postRelated/{id}/{commentId?}', [ComentController::class, 'postRelated']);
});



Route::group(['middleware' => ['jwt']], function () {
    Route::prefix('post')->group(function(){
        Route::get('/', [PostController::class, 'index']);
        Route::post('/create', [PostController::class, 'create']);
        Route::post('/update', [PostController::class, 'update']);
        Route::post('/delete', [PostController::class, 'delete']);
    });

    Route::prefix('reaction')->group(function(){
        Route::post('/create', [ReactionController::class, 'create']);
        Route::get('/users/{postId}/{id}', [ReactionController::class, 'users']);
    });

    Route::prefix('friend')->group(function(){
        Route::post('/add', [FriendController::class, 'create']);
        Route::post('/decline', [FriendController::class, 'decline']);
        Route::post('/accept', [FriendController::class, 'accept']);
        Route::post('/markAsRead', [FriendController::class, 'markAsRead']);
        Route::get('/pending', [FriendController::class, 'pending']);
        Route::get('/searchCurrentUser', [FriendController::class, 'searchCurrentUser']);
    });

    Route::prefix('comment')->group(function(){
        Route::post('/create', [ComentController::class, 'create']);
        Route::post('/update', [ComentController::class, 'update']);
        Route::post('/delete', [ComentController::class, 'delete']);
    });

    Route::prefix('auth')->group(function(){
        Route::get('/refreshToken', [AuthController::class, 'refreshToken']);
    });
});
