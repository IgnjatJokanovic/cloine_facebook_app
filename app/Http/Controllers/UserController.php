<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Friends;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

use function App\Providers\sendActivationEmail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResponse
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $fields = request()->all();

        $validator = Validator::make($fields, [
            'firstName' => 'required|string|max:255',
            'lastName' =>  'required|string|max:255',
            'birthday' =>  'required|date',
            'email' => 'required|email|unique:users',
            'password' => 'required|alpha_num',
        ]);

        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $birthday = Carbon::parse($fields['birthday']);
        $password = bcrypt($fields['password']);

        $fields['birthday'] = $birthday;
        $fields['password'] = $password;


        $user = User::create($fields);

        sendActivationEmail($user->email, $user->id);

        return response()->json("Thank you for registering, activation link has been sent to your email", 201);


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
    public function show(int $id): JsonResponse
    {
        $user = User::with('profilePhoto.image', 'coverPhoto.image')
                    ->where('id', $id)
                    ->first();

        if($user === null){
            return response()->json('User not found', 404);
        }

        return response()->json($user);
    }

    public function search()
    {
        $search = request()->search;

        $data = User::with('profilePhoto.image')
                    ->where('users.firstName', 'ILIKE', "%$search%")
                    ->orWhere('users.lastName', "ILIKE", "%$search%")
                    ->paginate(1);

        return response()->json($data);
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
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $fields = request()->all();

        $validator = Validator::make($fields, [
            'firstName' => 'required|string|max:255',
            'lastName' =>  'required|string|max:255',
            'birthday' =>  'required|date',
            'email' => "required|email|unique:users,email,$id",
        ]);

        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $message = 'Updated data';

        $birthday = Carbon::parse($fields['birthday']);

        $user = User::find($id);

        $reload = false;

        if($fields['email'] !== $user->email){

            $message = 'Email activation sent to new email please login again';
            $user->active = false;
            sendActivationEmail($user->email, $user->id);
            $reload = true;
        }

        $user->firstName = $fields['firstName'];
        $user->lastName = $fields['lastName'];
        $user->email = $fields['email'];
        $user->birthday = $birthday;

        $user->update();

        return response()->json(['msg' => $message, 'data' => $reload]);
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

    public function updatePhoto()
    {
        $fields = request()->all();

        $validator = Validator::make($fields, [
            'id' => 'required',
            'isProfile' =>  'required',
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

        $message = 'Changed profile photo';

        $postId = request()->id;

        if(request()->isProfile){
            $user->profile = $postId;
        }else{
            $user->cover = $postId;
            $message = 'Changed cover photo';
        }

        $user->update();

        return response()->json(['msg' => $message]);

    }
}
