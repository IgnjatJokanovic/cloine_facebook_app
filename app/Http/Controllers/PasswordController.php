<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class PasswordController extends Controller
{

    public function reset()
    {

        $fields = request(
            [
                'email',
            ]
        );

        $validator = Validator::make($fields, [
            'email' => 'required|email',

        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $fields['email'])
                    ->first();

        if($user === null){
            return response()->json(['error' => 'User not found with that email'], 404);
        }

        $token = md5($user->email . "facebook");

        PasswordResetToken::create([
            'token' => $token,
            'user_id' => $user->id,
        ]);

        $url = env("PASSWORD_RESET_URL") . "?token=$token";

        Mail::to($user->email)->queue(new PasswordResetMail($url));

        return response()->json('Password reset email sent check your inbox');
    }

    public function update()
    {
        $fields = request(
            [
                'password',
            ]
        );

        $validator = Validator::make($fields, [
            'password' => 'required',

        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');

        $user = User::find($userId);

        if($user === null){
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->password = bcrypt($fields['password']);
        $user->update();

        return response()->json("Password updated successfully");
    }

    public function change()
    {
        $fields = request(
            [
                'token',
                'password',
            ]
        );

        $validator = Validator::make($fields, [
            'token' => 'required',
            'password' => 'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $token = PasswordResetToken::where('token', $fields['token'])
                    ->first();

        if($token === null){
            return response()->json(['error' => "Password reset token not found"], 404);
        }

        $user = User::find($token->user_id);

        if($user === null){
            return response()->json(['error' => "User not found"], 404);
        }

        $user->password = bcrypt($fields['password']);
        $user->update();

        $token->delete();

        return response()->json("Password has been changed");
    }
}
