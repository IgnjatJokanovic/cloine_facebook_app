<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class MessageController extends Controller
{

    public function index()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = (int)$payload->get('id');

        $latest = Message::latestMessages($userId)
                    ->paginate(6);

        return response()->json($latest);
    }

    public function show(int $id)
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = (int)$payload->get('id');

        $messages = Message::where(function($q) use($id, $userId){
                        $q->where('from', $id)
                            ->where('to', $userId);
                    })
                    ->orWhere(function($q) use($id, $userId){
                        $q->where('to', $id)
                            ->where('from', $userId);
                    })
                    ->orderBy('created_at', 'ASC')
                    ->paginate(10);

        return response()->json($messages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = (int)$payload->get('id');

        $fields = request(
            [
                'to',
                'body',
            ]
        );

        $validator = Validator::make($fields, [
            'to' => 'required',
            'body' => 'required',
        ]);

        $fields['from'] = $userId;


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $message = Message::create($fields);

        return response()->json(['msg' => 'Sent message', 'data' => $message]);
    }

    public function search()
    {
        $search = request()->search;

        $payload = JWTAuth::parseToken()->getPayload();
        $userId = (int)$payload->get('id');

        $data = Message::search($userId, $search)
                        ->union(User::friendsQuerry($userId, $search))
                        ->paginate(6);

        return response()->json($data);

    }

    public function markAsRead()
    {
        $fields = request(
            [
                'ids',
            ]
        );

        $validator = Validator::make($fields, [
            'ids' => 'required|array',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
    }

    public function update()
    {

        $fields = request(
            [
                'body',
                'id',
            ]
        );

        $validator = Validator::make($fields, [
            'body' => 'required',
            'id' => 'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $message = Message::find($fields['id']);

        if($message === null){
            return response()->json(['error' => 'Message not found'], 404);
        }

        $message->body = $fields['body'];
        $message->update();

        return response()->json(['msg' => 'Updated message', 'data' => $message]);
    }


    public function delete()
    {
        $fields = request(
            [
                'id',
            ]
        );

        $validator = Validator::make($fields, [
            'id' => 'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $message = Message::find($fields['id']);

        if($message === null){
            return response()->json(['error' => 'Message not found'], 404);
        }

        $message->delete();

        return response()->json(['msg' => 'Deleted message']);
    }
}
