<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class NotificationController extends Controller
{

    public function index()
    {
        /** @var User $user */
        $user = auth()->user();

        $data = Notification::with('user.profilePhoto.image')
                    ->where('user_id', $user->id)
                    ->orderBy('opened', "ASC")
                    ->orderBy('created_at', "ASC")
                    ?->paginate(6);

        return response()->json($data);
    }


    public function markAsRead()
    {
        $fields = request(
            [
                'id',
            ]
        );

        $validator = Validator::make($fields, [
            'id' => 'required|numeric',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $notification = Notification::find($fields['id']);

        if($notification === null){
            return response()->json(['error' => 'Notification not found']);
        }

        $notification->opened = true;
        $notification->update();

        return response()->json('');

    }

    public function markAllAsRead()
    {
        /** @var User $user */
        $user = auth()->user();

        Notification::where('user_id', $user->id)
            ->update(['opened' => true]);

        return response()->json('Marked all as read');
    }


    public function unreadCount()
    {
         /** @var User $user */
         $user = auth()->user();

        $count = Notification::where('user_id', $user->id)
                    ->where('opened', false)
                    ->count();

        return response()->json($count);
    }
}
