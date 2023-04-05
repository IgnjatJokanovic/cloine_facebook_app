<?php

namespace App\Http\Controllers;

use App\Mail\ActivationMail;
use App\Models\ActivationToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

use function App\Providers\sendActivationEmail;

class ActivationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function activate()
    {
        $token = ActivationToken::where('token', request()->token)
                    ->first();

        if($token === null){
            return response()->json(['error' => 'Token not found'], 404);
        }

        $user = User::find($token->user_id);

        if($user->active){
            return response()->json(['error' => 'Already activated the account'], 422);
        }

        if($user === null){
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->active = true;
        $user->update();
        $token->delete();

        return response()->json('Successfully activated account');


    }

    public function resend()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $user = User::find($id);

        if($user === null){
            return response()->json(['error' => 'User not found'], 404);
        }

        sendActivationEmail($user->email, $id);

        return response()->json('Activation email sent');
    }


}
