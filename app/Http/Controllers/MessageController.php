<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
    public function update()
    {
        //
    }


    public function delete()
    {
        //
    }
}
