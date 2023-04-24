<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

use function App\Providers\notifyNotificationRecieved;

class ComentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        $fields = request(
            [
                'post_id',
                'comment_id',
                'body',

            ]
        );

        $validator = Validator::make($fields, [
            'post_id' => 'required',
            'body' =>  'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $post = Post::find($fields['post_id']);

        if($post === null){
            return response()->json(['error' => 'Post not found'], 404);
        }

        $payload = JWTAuth::parseToken()->getPayload();
        $userId = $payload->get('id');

        $fields['user_id'] = $userId;

        $existingComment = null;

        if($fields['comment_id'] != null){
            $existingComment = Comment::find($fields['comment_id']);

            if($existingComment === null){
                return response()->json('Comment not found', 404);
            }
        }

        $comment = Comment::create($fields);
        $comment->load('user.profilePhoto.image');

        if($post->owner !== $post->creator){
            if($post->owner !== $userId){

                Notification::create([
                    'body' => 'Commented on you post',
                    'user_id' => $post->owner,
                    'creator' => $userId,
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'type' => 'comment',
                ]);
            }

            if($post->creator !== $userId){

                Notification::create([
                    'body' => 'Commented on you post',
                    'user_id' => $post->creator,
                    'creator' => $userId,
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'type' => 'comment',
                ]);
            }

        }else{
            if($post->owner !== $userId){

                Notification::create([
                    'body' => 'Commented on you post',
                    'user_id' =>  $post->owner,
                    'creator' => $userId,
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'type' => 'comment',
                ]);
            }
        }


        if($existingComment !== null && $existingComment?->user_id !== $userId){
            Notification::create([
                'body' => 'Replied to your comment',
                'user_id' => $existingComment->user_id,
                'creator' => $userId,
                'post_id' => $post->id,
                'comment_id' => $comment->id,
                'type' => 'comment',
            ]);
        }

        return response()->json([
            'data' => $comment,
            'msg' => 'Commented successfully'
        ]);


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
    public function postRelated(int $id, ?int $commentId = null)
    {
        $comments = Comment::with(
                                'user.profilePhoto.image'
                            )
                           ->where('post_id', $id)
                           ->where('comment_id', $commentId);

        return response()->json($comments->paginate(6));
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

        $fields = request(
            [
                'body',
                'id'
            ]
        );

        $validator = Validator::make($fields, [
            'body' =>  'required',
            'id' => "required",
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }


        $comment = Comment::find(request()->id);

        if($comment === null){
            return response()->json('Comment not found', 404);
        }

        $comment->body = request()->body;
        $comment->update();

        return response()->json(['msg' => "Updated comment", 'data' => $comment]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $id = request()->id;
        $comment = Comment::find($id);

        if($comment === null){
            return response()->json('Comment not found', 404);
        }

        $comment->delete();

        return response()->json(['msg' => 'Deleted comment', 'data' => $id]);
    }
}
