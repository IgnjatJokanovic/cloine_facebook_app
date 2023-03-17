<?php

namespace App\Http\Controllers;

use App\Events\FriendshipSent;
use App\Events\PostReactedAction;
use App\Events\PostReaction;
use Illuminate\Http\Request;
use Validator;
use App\Models\Post;
use App\Models\Reaction;
use Exception;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReactionController extends Controller
{
    public function create()
    {
        $fields = request(
            [
                'post_id',
                'reaction_id',
            ]
        );

        $validator = Validator::make($fields, [
            'post_id' => 'required',
            'reaction_id' => 'required',
        ]);

        try{
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = $payload->get('id');
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 422);
        }


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $reacted = Reaction::where([
            'user_id' => $userId,
            'post_id' => request()->post_id
            ])->first();

        if($reacted !== null){
            // handle change
            if($reacted->reaction_id !== request()->reaction_id){
                $reacted->load('emotion', 'user');
                broadcast(new PostReactedAction($reacted, 'removed'));
                $reaction = $this->react(request()->post_id, $userId, $fields);
                $reacted->delete();
                return response()->json(['msg' => 'Reacted', 'data' => $reaction], 200);
            }
            //handle delete
            $reaction = $reacted;
            $reaction->load('emotion', 'user');
            broadcast(new PostReactedAction($reaction, 'removed'));
            $reacted->delete();
            return response()->json(['msg' => 'Removed reaction', 'data' => null], 200);
        }

        $reaction = $this->react(request()->post_id, $userId, $fields);
        $reaction->load('emotion', 'user');

        return response()->json(['msg' => 'Reacted', 'data' => $reaction], 200);
    }

    public function react($id, $userId, $fields)
    {
        $post = Post::find($id);
        if($post === null){
            return response()->json(['error' => 'Post not found'], 422);
        }

        $fields['user_id'] = $userId;

        $reaction = Reaction::create($fields);
        $reaction->load('emotion', 'user');

        broadcast(new PostReactedAction($reaction, 'add'));
        // dd(1);
        return $reaction;

    }

    public function users(int $postId, int $id)
    {
        $reactions = Reaction::with('user.profilePhoto.image')
                            ->where(function($q) use($postId, $id){
                                $q->where('post_id', $postId);

                                if($id != 0){
                                    $q->where('reaction_id', $id);
                                }
                            });

        return response()->json($reactions->paginate(6));

    }
}
