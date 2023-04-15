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
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $data = Notification::with('user')
                    ->where('user_id', $id)
                    ->orderBy('created_at', "DESC")
                    ->paginate(6);

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
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        Notification::where('user_id', $id)
            ->update(['opened' => true]);

        return response()->json('Marked all as read');
    }


    public function unreadCount()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $count = Notification::where('user_id', $id)
                    ->where('opened', false)
                    ->count();

        return response()->json($count);
    }
}
