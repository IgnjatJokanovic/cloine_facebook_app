<?php

namespace App\Http\Controllers;

use App\Dto\FriendRequestDto;
use App\Models\Friend;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

use function App\Providers\notifyFriendRequestSent;
use function App\Providers\notifyNotificationRecieved;

class FriendController extends Controller
{
    public function pending()
    {
        /** @var User $user */
        $user = auth()->user();

        $pending = $user->pending()?->paginate(5);

        return response()->json($pending);
    }

    public function userFriends(int $id, int|null $take = null)
    {
        $friends =  User::friendsQuerry($id);

        if($take){
            return response()->json($friends->take(6)->get());
        }

        return response()->json($friends->paginate(6));
    }

    public function unreadCount()
    {
        /** @var User $user */
        $user = auth()->user();

        $count = $user->pending()->where('opened', false)->count();

        return response()->json($count);
    }


    public function markAllAsRead()
    {
        /** @var User $user */
        $user = auth()->user();

        Friend::where('to', $user->id)
            ->update(['opened' => true]);

        return response()->json(['msg' => 'Marked all as read']);
    }

    public function create()
    {
        $fields = request(['to']);

        $validator = Validator::make($fields, [
            'to' => 'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = User::find($fields['to']);

        if($user === null){
            return response()->json("User not found", 404);
        }

        /** @var User $user */
        $user = auth()->user();

        $fields['from'] = $user->id;

        $rel = Friend::create($fields);

        return response()->json(['msg' => "Friend request sent", 'data' => $rel], 200);
    }


    public function searchCurrentUser()
    {
        /** @var User $user */
        $user = auth()->user();

        $search = request()->search;
        $exlude = request()->exlude;

        $userId = $user->id;

        $friends =  User::friendsQuerry($userId, $search);

        if((int)$exlude !== (int)$userId){
            Log::debug("$exlude, $userId");
            $friends->where('users.id', '!=', $exlude);
        }

        return response()->json($friends->paginate(6));
    }


    public function recomended()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $related = User::recomendedFriends($payload->get('id'))
                            ->paginate(10);

        return response()->json($related);

    }

    public function markAsRead()
    {
        /** @var User $user */
        $user = auth()->user();

        $id = request()->id;

        $user->friendsFrom()->updateExistingPivot($id, ['opened' => true]);

        return response()->json(['msg' => '']);
    }

    public function accept()
    {
        $id = request()->id;

        /** @var User $user */
        $user = auth()->user();
        $user->friendsFrom()->updateExistingPivot($id, ['accepted' => true]);


        Notification::create([
            'body' => 'Accepted your friend request',
            'user_id' => $id,
            'creator' => $user->id,
            'type' => 'friendship',
        ]);


        return response()->json('Accepted friend request', 201);
    }

    public function decline()
    {
        $id = request()->id;
        /** @var User $user */
        $user = auth()->user();

        $friendRequest = Friend::where(function ($q) use ($id, $user){
                $q->where('to', $id)
                ->where('from', $user->id);
            })
            ->orWhere(function($q) use ($id, $user){
                $q->where('to', $user->id)
                ->where('from', $id);
            })
            ->first();

        if($friendRequest === null){
            return response()->json(['error' => 'Friend request nor found']);
        }

        $friendRequest->delete();

        return response()->json('Declined friend request', 201);
    }
}
