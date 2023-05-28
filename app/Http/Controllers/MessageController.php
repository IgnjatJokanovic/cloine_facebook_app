<?php

namespace App\Http\Controllers;

use App\Dto\MessageDto;
use App\Dto\MessageNotificationDto;
use App\Events\MessageRecieved;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function unreadCount()
    {
        /** @var User $user */
        $user = auth()->user();

        $count = Message::where('to', $user->id)
                    ->where('opened', false)
                    ->count();

        return response()->json($count);
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

    public function latest(int $id)
    {
        /** @var User $user */
        $user = auth()->user();

        $message = Message::latest($id, $user->id);

        return response()->json(['message' => $message]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /** @var User $user */
        $user = auth()->user();

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

        $fields['from'] = $user->id;


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
                        ->orderBy('id')
                        ->paginate(6);

        return response()->json($data);

    }

    public function markAsRead()
    {
        /** @var User $user */
        $user = auth()->user();

        $fields = request(
            [
                'ids',
                'related',
            ]
        );

        $validator = Validator::make($fields, [
            'ids' => 'required|array',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $counter = 0;

        foreach($fields['ids'] as $id){
            $msg = Message::find($id);
            $msg->opened = true;
            $msg->save();
            $counter++;
        }

        Log::debug($user->id);
        Log::debug(request()->related);

        $open = Message::where([
            'to' => $user->id,
            'from' => request()->related,
            'opened' => false,
        ])->toSql();

        Log::debug($open);


        $open = Message::where([
            'to' => $user->id,
            'from' => request()->related,
            'opened' => false,
        ])->count() == 0;

        Log::debug($open);

        return response()->json(['count' => $counter, 'opened' => $open]);
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

        /** @var User $user */
        $user = auth()->user();

        $message = Message::find($fields['id']);

        if($message === null){
            return response()->json(['error' => 'Message not found'], 404);
        }

        $message->delete();

        $data = Message::latest($message->to, $user->id);



        return response()->json(['msg' => 'Deleted message', 'data' => $data]);
    }
}
