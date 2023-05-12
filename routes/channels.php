<?php

use App\Models\Reaction;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('friendRequestSent.{id}', function($user, $id) {
    return (int) $user->to === (int) $id;
});

Broadcast::channel('friendRequestCanceled.{id}', function($user, $id) {
    return (int) $user->to === (int) $id;
});

Broadcast::channel('postReaction.{id}', function($reaction, $id) {
    return (int) $reaction->post_id === (int) $id;
});

Broadcast::channel('notification.{id}', function($notification, $id) {
    return (int) $notification->user_id === (int) $id;
});

Broadcast::channel('notificationRemoved.{id}', function($notification, $id) {
    return (int) $notification->to === (int) $id;
});

Broadcast::channel('newMessage.{id}', function($message, $id) {
    return (int) $message->to === (int) $id;
});

Broadcast::channel('messageRemoved.{id}', function($message, $id) {
    return (int) $message->to === (int) $id;
});

Broadcast::channel('messageUpdated.{id}', function($obj, $id) {
    return (int) $obj->to === (int) $id;
});

