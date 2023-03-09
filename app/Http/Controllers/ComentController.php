<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Validator;

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
                'user_id',
                'body',

            ]
        );

        $validator = Validator::make($fields, [
            'post_id' => 'required',
            'user_id' => 'required',
            'body' =>  'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $existingComment = null;

        if($fields['comment_id'] != null){
            $existingComment = Comment::find($fields['comment_id']);

            if($existingComment === null){
                return response()->json('Comment not found', 404);
            }
        }

        $comment = Comment::create($fields);
        $comment->load('user.profilePhoto.image');

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
                           ->where('post_id', $id);

        if($commentId !== null){
            $comments->where('comment_id', $commentId);
        }

        return response()->json($comments->paginate(6));
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
}
