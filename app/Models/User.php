<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use SebastianBergmann\Type\FalseType;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTAuth;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'birthday',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $appends = ['accepted', 'opened', 'to', 'isFriends'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getAcceptedAttribute($value)
    {
        return $this->pivot?->accepted;
    }

    public function getOpenedAttribute($value)
    {
        return $this->pivot?->opened;
    }

    public function getToAttribute($value)
    {
        return $this->pivot?->to;
    }

    public function getIsFriendsAttribute($value)
    {
        $userId = null;

        try{
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = (int)$payload->get('id');
        }catch(Exception $e){
        }

        $isFriends = null;

        if($userId !== (int)$this->id && $userId !== null){

            $isFriends = DB::table('friends')
                            ->where(function ($query) use ($userId) {
                                $query->where([
                                    'from' => $userId,
                                    'to'   => $this->id,
                                ])->orWhere(function ($query) use ($userId) {
                                    $query->where([
                                            'from' => $this->id,
                                            'to' => $userId,
                                        ]);
                                });
                            })
                            ->first();
        }

        return $isFriends;
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'birthday' => $this->birthday,
            'profile' => $this->profilePhoto()->with('image')->first(),
        ];
    }

    public function profilePhoto()
    {
        return $this->belongsTo(Post::class, 'profile');
    }

    public function coverPhoto()
    {
        return $this->belongsTo(Post::class, 'cover');
    }


    public function friendsFrom()
    {
        return $this->belongsToMany(self::class, 'friends', 'to', 'from')->withPivot(['accepted', 'opened', 'to', 'from']);
    }

    public function friendsTo()
    {
        return $this->belongsToMany(self::class, 'friends', 'from', 'to')->withPivot(['accepted', 'opened', 'to', 'from']);
    }

    public function accepted()
    {
        return $this->friendsFrom()
                    ->where("accepted", true)
                    ->merge(
                        $this->friendsTo()
                             ->where('accepted', true)
                    );
    }



    public function pending()
    {
        return $this->friendsFrom()
                    ->with('profilePhoto.image')
                    ->where('friends.accepted', false)
                    ->orderBy('friends.opened', 'desc');
    }

    public static function friendsQuerry(int $id, ?string $search = null, ?int $excluded = null)
    {
        $query = DB::table('friends')
                ->select(
                    'users.id',
                    'users.firstName',
                    'users.lastName',
                    'i1.src as profile',
                )
                ->join('users', function ($join) use ($id) {
                    $join->on(DB::raw('CASE friends.to WHEN '.$id.' THEN friends.from ELSE friends.to END'), '=', 'users.id');
                })
                ->leftJoin('posts as p1', 'users.profile', '=', 'p1.id')
                ->leftJoin('images as i1', 'p1.image_id', '=', 'i1.id')
                ->where('friends.accepted', true)
                ->where(function($q) use($id){
                    $q->where('friends.to', $id)
                    ->orWhere('friends.from', $id);
                });

        if($search){
            $query->where(function($q) use($search){
                $q->where('users.firstName', 'ILIKE', "%$search%")
                  ->orWhere('users.lastName', "ILIKE", "%$search%");
            });
        }

        if($excluded){
            $query->where('users.id', $excluded);
        }

        return $query;
    }

    public static function recomendedFriends(int $id)
    {
       $friendIds = DB::table('friends')
                ->select(
                    'users.id',
                )
                ->join('users', function ($join) use ($id) {
                    $join->on(DB::raw('CASE friends.to WHEN '.$id.' THEN friends.from ELSE friends.to END'), '=', 'users.id');
                })
                ->where('friends.accepted', true)
                ->where(function($q) use($id){
                    $q->where('friends.to', $id)
                    ->orWhere('friends.from', $id);
                }
                )->get()
                ->pluck('id')
                ->toArray();

        Log::debug($friendIds);
        $friendsOfFriends = DB::table('friends')
                            ->select(
                                'users.id',
                                'users.firstName',
                                'users.lastName',
                                'i1.src as profile',
                            )
                            ->join('users', function ($join) use ($friendIds) {
                                $join->on(DB::raw('CASE WHEN (friends.to IN ('.implode(',', $friendIds).')) THEN friends.from ELSE friends.to END'), '=', 'users.id');
                            })
                            ->leftJoin('posts as p1', 'users.profile', '=', 'p1.id')
                            ->leftJoin('images as i1', 'p1.image_id', '=', 'i1.id')
                            ->where('friends.accepted', true)
                            ->where(function($q) use($friendIds){
                                $q->whereIn('friends.to', $friendIds)
                                ->orWhereIn('friends.from', $friendIds);
                            })
                            ->where(function($q) use($id){
                                $q->whereNot('friends.to', $id)
                                ->whereNot('friends.from', $id);
                            });

        return $friendsOfFriends;
    }
}
