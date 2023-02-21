<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use SebastianBergmann\Type\FalseType;
use Tymon\JWTAuth\Contracts\JWTSubject;

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


    protected $appends = ['accepted', 'opened', 'to'];

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

    // public function getFromdAttribute($value)
    // {
    //     return $this->pivot?->from;
    // }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'birthday' => $this->birthday
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
}
