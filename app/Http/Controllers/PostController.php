<?php

namespace App\Http\Controllers;

use App\Events\FriendshipSent;
use App\Models\Image;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

use function App\Providers\notifyNotificationRecieved;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $id = $payload->get('id');

        $friendIds = User::friendIds($id);

        array_push($friendIds, $id);

        $posts = Post::with(
                        [
                            'owner.profilePhoto.image',
                            'creator.profilePhoto.image',
                            'image',
                            'emotion',
                            'distinctReactions',
                            'taged',
                        ]
                    )
                    ->withCount('distinctReactions')
                    ->where(function($q) use($friendIds){
                        $q->whereIn('owner', $friendIds)
                            ->orWHereIn('creator', $friendIds);
                    })
                    ->orderBy('created_at', 'DESC')
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
                'taged',
                'isProfile'
            ]
        );

        $validator = Validator::make($fields, [
            'owner' => 'required',
            'creator' => 'required',
            'body' =>  'required_without_all:image,emotion,taged',
            'image.src' => 'required_without_all:body,emotion,taged',
            'emotion' => 'required_without_all:body,image,taged',
            'taged' => 'required_without_all:body,emotion,image',
        ], [
            'body.required_without_all' => 'Cant make empty post, fill something',
            'image.src.required_without_all' => 'Cant make empty post, fill something',
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
        $post = Post::create($fields);
        $taged = request()->taged;

        $msg = 'Post created';

        if($taged !== null){
            $ids = collect($taged)->pluck('id');
            $post->taged()->attach($ids);

            foreach($ids as $id){

                notifyNotificationRecieved(
                    'Has tagged you in a post',
                    $id,
                    $fields['creator'],
                    $post->id
                );

            }
        }

        if($fields['owner'] !== $fields['creator']){

            notifyNotificationRecieved(
                'Wrote something on your wall',
                $fields['owner'],
                $fields['creator'],
                $post->id
            );
        }

        $isProfile = $fields['isProfile'] ?? null;

        $msg = "Post created";

        if($isProfile !== null){
            $post->load('image');
            $msg = 'Updated cover photo';

            if($fields['isProfile']){
                $msg = 'Updated profile photo';
            }

            self::updateUserImage($post->id, $fields['isProfile']);
        }

        return response()->json(['msg' => $msg, 'data' => $post]);
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
                            'owner.profilePhoto.image',
                            'creator.profilePhoto.image',
                            'image',
                            'emotion',
                            'distinctReactions',
                            'taged',
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
                        'owner.profilePhoto.image',
                        'creator.profilePhoto.image',
                        'image',
                        'emotion',
                        'distinctReactions',
                        'taged.profilePhoto.image',
                    ]
                )
                ->where('id', $id)
                ->first();

        if($post === null){
            return response()->json(['error' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

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
            'image.src' => 'required_without_all:body,emotion,taged',
            'emotion' => 'required_without_all:body,image,taged',
            'taged' => 'required_without_all:body,emotion,image',
        ], [
            'body.required_without_all' => 'Cant make empty post, fill something',
            'image.src.required_without_all' => 'Cant make empty post, fill something',
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

        $taged = request()->taged ?? [];

        if(count($taged) > 0){
            $ids = collect($taged)->pluck('id')->toArray();
            $old = $post->taged()->pluck('user_id')->toArray();
            $post->taged()->sync($ids);

            $remove = array_diff($old, $ids);;

            foreach($ids as $id){
                if(!in_array($id, $old)){

                    notifyNotificationRecieved(
                        'Has tagged you in a post',
                        $id,
                        $post->creator,
                        $post->id
                    );
                }

            }

            if(!empty($remove)){
                Notification::where('post_id', $post->id)
                            ->whereIn('user_id', $remove)
                            ->delete();
            }
        }

        $image = self::uploadImage($fields['image']);
        $emotion = $fields['emotion']['id'] ?? null;

        $fields['image_id'] = $image;
        $fields['emotion_id'] = $emotion;

        $post->body = request()->body;
        $post->image_id = $image;
        $post->emotion_id = $emotion;
        $post->update();

        $post = Post::with([
                    'owner.profilePhoto.image',
                    'creator.profilePhoto.image',
                    'image',
                    'emotion',
                    'distinctReactions',
                    'taged.profilePhoto.image',
                ])
                ->withCount('distinctReactions')
                ->where('id', request()->id)
                ->first();



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
            \File::delete(public_path() . $image->src);
        }
        $post->delete();

        return response()->json(['msg' => 'Post deleted'], 200);
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

    private static function updateUserImage(int $id, bool $isProfile = false): void
    {
        $payload = JWTAuth::parseToken()->getPayload();
        $idUser = $payload->get('id');
        $user = User::find($idUser);

        if($isProfile){
            $user->profile = $id;
        }else{
            $user->cover = $id;
        }

        $user->update();
    }
}
