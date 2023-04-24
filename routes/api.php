<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmojiController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Translation\MessageCatalogue;

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

Route::prefix('activation')->group(function(){
    Route::post('/activate', [ActivationController::class, 'activate']);
});

Route::prefix('password')->group(function(){
    Route::post('/reset', [PasswordController::class, 'reset']);
    Route::post('/change', [PasswordController::class, 'change']);
});




Route::group(['middleware' => ['jwt']], function () {
    Route::prefix('post')->group(function(){
        Route::get('/', [PostController::class, 'index']);
    });

    Route::prefix('reaction')->group(function(){
        Route::get('/users/{postId}/{id}', [ReactionController::class, 'users']);
    });

    Route::prefix('notification')->group(function(){
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/markAsRead', [NotificationController::class, 'markAsRead']);
        Route::post('/markAllAsRead', [NotificationController::class, 'markAllAsRead']);
        Route::get('/unreadCount', [NotificationController::class, 'unreadCount']);
    });

    Route::prefix('friend')->group(function(){
        Route::post('/markAsRead', [FriendController::class, 'markAsRead']);
        Route::post('/markAllAsRead', [FriendController::class, 'markAllAsRead']);
        Route::get('/pending', [FriendController::class, 'pending']);
        Route::get('/searchCurrentUser', [FriendController::class, 'searchCurrentUser']);
        Route::get('/recomended', [FriendController::class, 'recomended']);
        Route::get('/unreadCount', [FriendController::class, 'unreadCount']);
    });

    Route::prefix('user')->group(function(){
        Route::post('/search', [UserController::class, 'search']);
        Route::post('/update', [UserController::class, 'update']);
    });

    Route::prefix('auth')->group(function(){
        Route::get('/refreshToken', [AuthController::class, 'refreshToken']);
    });

    Route::prefix('activation')->group(function(){
        Route::post('/resend', [ActivationController::class, 'resend']);
    });

    Route::prefix('message')->group(function(){
        Route::get('/', [MessageController::class, 'index']);
        Route::get('/show/{id}', [MessageController::class, 'show']);
        Route::post('/search', [MessageController::class, 'search']);
        Route::post('/markAsRead', [MessageController::class, 'markAsRead']);
        Route::get('/unreadCount', [MessageController::class, 'unreadCount']);
    });

    Route::prefix('password')->group(function(){
        Route::post('/update', [PasswordController::class, 'update']);
    });

    Route::group(['middleware' => ['active']], function(){
        Route::prefix('message')->group(function(){
            Route::post('/create', [MessageController::class, 'create']);
            Route::post('/update', [MessageController::class, 'update']);
            Route::post('/delete', [MessageController::class, 'delete']);
        });

        Route::prefix('user')->group(function(){
            Route::post('/updatePhoto', [UserController::class, 'updatePhoto']);
        });

        Route::prefix('comment')->group(function(){
            Route::post('/create', [ComentController::class, 'create']);
            Route::post('/update', [ComentController::class, 'update']);
            Route::post('/delete', [ComentController::class, 'delete']);
        });

        Route::prefix('friend')->group(function(){
            Route::post('/add', [FriendController::class, 'create']);
            Route::post('/decline', [FriendController::class, 'decline']);
            Route::post('/accept', [FriendController::class, 'accept']);
        });

        Route::prefix('reaction')->group(function(){
            Route::post('/create', [ReactionController::class, 'create']);
        });

        Route::prefix('post')->group(function(){
            Route::post('/create', [PostController::class, 'create']);
            Route::post('/update', [PostController::class, 'update']);
            Route::post('/delete', [PostController::class, 'delete']);
        });

    });

});
