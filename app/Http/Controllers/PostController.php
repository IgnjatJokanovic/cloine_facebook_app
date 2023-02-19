<?php

namespace App\Http\Controllers;

use App\Events\FriendshipSent;
use App\Models\Image;
use App\Models\Post;
use App\Models\Reaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::with('distinctReactions')
                    ->withCount('distinctReactions')
                    ->paginate(10);

        return response()->json($posts);
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
                'owner',
                'creator',
                'body',
                'image',
                'emotion',
                'taged'
            ]
        );

        $validator = Validator::make($fields, [
            'owner' => 'required',
            'creator' => 'required',
            'body' =>  'required_without_all:image,emotion,taged',
            'image' => 'required_without_all:body,emotion,taged',
            'emotion' => 'required_without_all:body,image,taged',
            'taged' => 'required_without_all:body,emotion,image',
        ], [
            'body.required_without_all' => 'Cant make empty post, fill something',
            'image.required_without_all' => 'Cant make empty post, fill something',
            'emotion.required_without_all' => 'Cant make empty post, fill something',
            'taged.required_without_all' => 'Cant make empty post, fill something',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $image = self::uploadImage($fields['image']);
        $emotion = $fields['emotion']['id'] ?? null;

        $fields['image_id'] = $image;
        $fields['emotion_id'] = $emotion;
        $post = Post::create($fields)->id;

        return response()->json(['msg' => 'Post created', 'id' => $post]);
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

    public function userRelated(int $id)
    {
        $posts = Post::with(
                        [
                            'owner',
                            'creator',
                            'image',
                            'emotion',
                            'distinctReactions',
                        ]
                    )
                    ->where(function($q) use ($id){
                        $q->where('creator', $id)
                          ->orWhere('owner', $id);
                    })
                    ->orderByDesc('posts.created_at')
                    ->paginate(6);

        return response()->json($posts);
    }

    public function userRelatedPhotos(int $id, int|null $take = null)
    {
        $posts = Post::with('image')
                    ->where('creator', $id)
                    ->where('image_id', '!=', null);

        if($take){
            return response()->json($posts->take(6)->get());
        }

        return response()->json($posts->paginate(6));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // dd($id);
        $post = Post::with(
                    [
                        'owner',
                        'creator',
                        'image',
                        'emotion',
                        'distinctReactions'
                    ]
                )
                ->where('id', $id)
                ->first();

        if($post === null){
            return response()->json(['error' => 'Post not found'], 404);
        }

        return response()->json($post);
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
        $fields = request(
            [
                'id',
                'body',
                'image',
                'emotion',
                'taged'
            ]
        );

        $validator = Validator::make($fields, [
            'id' => 'required',
            'body' =>  'required_without_all:image,emotion,taged',
            'image' => 'required_without_all:body,emotion,taged',
            'emotion' => 'required_without_all:body,image,taged',
            'taged' => 'required_without_all:body,emotion,image',
        ], [
            'body.required_without_all' => 'Cant make empty post, fill something',
            'image.required_without_all' => 'Cant make empty post, fill something',
            'emotion.required_without_all' => 'Cant make empty post, fill something',
            'taged.required_without_all' => 'Cant make empty post, fill something',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $post = Post::find(request()->id);

        if($post === null){
            return response()->json(['error' => 'Post not found'], 404);
        }

        $image = self::uploadImage($fields['image']);
        $emotion = $fields['emotion']['id'] ?? null;

        $fields['image_id'] = $image;
        $fields['emotion_id'] = $emotion;

        $post->body = request()->body;
        $post->image_id = $image;
        $post->emotion_id = $emotion;
        $post->update();

        $post = Post::with('owner', 'creator', 'image', 'emotion')->where('id', request()->id)->first();



        return response()->json(['msg' => 'Post updated', 'post' => $post, 'id' => request()->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $post = Post::find(request()->id);

        if($post === null){
            return response()->json('Post not found', 404);
        }
        $image = $post->image()->first();
        if($image !== null){
            Log::debug('uso');
            \File::delete(public_path() . $image->src);
        }
        $post->delete();

        return response()->json('Post deleted', 200);
    }

    private static function uploadImage(array $fileArray): int|null
    {
        // just upload image if is null before
        if($fileArray['id'] === null && str_contains($fileArray['src'], 'data:image')){
            Image::destroy($fileArray['id']);
            return self::uploadBase64($fileArray['src']);
        }
        // cleanup image if already exists and setting new
        if($fileArray['id'] !== null && str_contains($fileArray['src'], 'data:image')){
            Image::destroy($fileArray['id']);
            return self::uploadBase64($fileArray['src']);
        }
        // cleanup image if already exists
        if($fileArray['id'] !== null && $fileArray['src'] === null){
            Image::destroy($fileArray['id']);
            return null;
        }

        return $fileArray['id'];


    }

    private static function uploadBase64(string $base64string): int
    {
        $name = time()."-post.jpg";
        $image = base64_decode(substr($base64string, strpos($base64string, ",") + 1));
        $path = public_path() . "/img/$name";

        \File::put($path, $image);

        return Image::create(['src' => "/img/$name"])->id;
    }
}
