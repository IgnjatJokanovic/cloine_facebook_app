<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class AuthController extends Controller
{
    public function login()
    {
        $credentials = request(['email', 'password']);

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|alpha_num',
        ]);

        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }



        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Invalid ursername or password'], 401);
        }

        return response()->json(['token' => $token]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refreshToken()
    {
        JWTAuth::parseToken()->refresh();

        $user = JWTAuth::user();

        $customClaims = $user->getJWTCustomClaims();

        $newToken = JWTAuth::claims($customClaims)->fromUser($user);

        return response()->json($newToken, 200);

    }
}
