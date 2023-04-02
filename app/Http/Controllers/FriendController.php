<?php

namespace App\Http\Controllers;

use App\Dto\FriendRequestDto;
use App\Events\FrieendRequestSent;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class FriendController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pending()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $user = User::find($id);

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




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
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

        $payload = JWTAuth::parseToken()->getPayload();
        $fields['from'] = $payload->get('id');

        $user = User::with('profilePhoto', 'profilePhoto.image')
                    ->where('id', $fields['from'])
                    ->first();
        $userDto = new FriendRequestDto(
            $user->id,
            $fields['to'],
            $user->firstName,
            $user->lastName,
            $user->profile_photo,
            false,
            false,
        );

        broadcast(new FrieendRequestSent($userDto));

        $rel = Friend::create($fields);

        return response()->json(['msg' => "Friend request sent", 'data' => $rel], 200);


    }


    public function searchCurrentUser()
    {
        $search = request()->search;
        $exlude = request()->exlude;
        Log::debug(request()->all());
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = (int)$payload->get('id');

        $friends =  User::friendsQuerry($userId, $search);

        if((int)$exlude !== (int)$userId){
            Log::debug("$exlude, $userId");
            $friends->where('users.id', '!=', $exlude);
        }

        return response()->json($friends->paginate(6));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
        $id = request()->id;
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');

        $this->updatePivot($id, $userId, 'opened', true);

        return response()->json('Declined friend request', 201);
    }

    public function accept()
    {
        $id = request()->id;
        $user = auth()->user();
        Log::debug('accepting');
        $user->friendsFrom()->updateExistingPivot($id, ['accepted' => true]);

        return response()->json('Accepted friend request', 201);
    }

    public function decline()
    {
        $id = request()->id;
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');
        Log::debug('accepting');
        $friend = Friend::where(function ($q) use ($id, $userId){
            $q->where('to', $id)
            ->orWhere('from', $userId);
        })
        ->orWhere(function($q) use ($id, $userId){
            $q->where('to', $userId)
            ->orWhere('from', $id);
        })->first();

        $friend->delete();

        return response()->json('', 201);
    }



    private function updatePivot($id, $userId, $property, $value)
    {
        $friend = Friend::where(function ($q) use ($id, $userId){
                $q->where('to', $id)
                ->orWhere('from', $userId);
            })
            ->orWhere(function($q) use ($id, $userId){
                $q->where('to', $userId)
                ->orWhere('from', $id);
            })->first();

            if($friend === null){
                return response()->json('User not found', 404);
            }

            $friend->{$property} = $value;
            $friend->update();
    }
}
