<?php

namespace App\Providers;

use App\Dto\FriendRequestDto;
use App\Events\FriendRequestCanceled;
use App\Events\FriendRequestSent;
use App\Events\NewNotification;
use App\Events\PostReactedAction;
use App\Models\Friend;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class NotifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        function notifyReaction($reaction, string $action): void
        {
            Log::debug("XCXXX");
            broadcast(new PostReactedAction($reaction, $action));
        }

        function notifyFriendRequestSent(Friend $friend): void
        {
            broadcast(new FriendRequestSent($friend));
        }

        function notifyFriendRequesCanceled(int $to, int $id): void
        {
            broadcast(new FriendRequestCanceled($to, $id))->toOthers();
        }

        function notifyNotificationRecieved(
            string $message,
            int $id,
            int $creator,
            int $postId = null,
            int $commentId = null,
            string $type = null,
        ): void
        {
            Notification::create([
                'body' => $message,
                'user_id' => $id,
                'creator' => $creator,
                'post_id' => $postId,
                'comment_id' => $postId,
                'type' => $type,
            ]);
        }

        function notifyMessageRecieved(FriendRequestDto $userDto): void
        {
            broadcast(new FriendRequestSent($userDto));
        }
    }
}
