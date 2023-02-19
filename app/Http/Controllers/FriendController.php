<?php

namespace App\Http\Controllers;

use App\Dto\FriendRequestDto;
use App\Events\FrieendRequestSent;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
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

        $pending = $user->pending()->paginate(5);

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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');

        $this->updatePivot($id, $userId, 'accepted', true);

        return response()->json('Accepted friend request', 201);
    }

    public function decline()
    {
        $id = request()->id;
        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');

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
