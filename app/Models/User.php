<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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


    public function allFriends()
    {
        return $this->belongsToMany(self::class, 'friends', 'to', 'from')->withPivot(['accepted', 'opened', 'to', 'from']);
    }

    public function hasFriend($id)
    {
        return $this->allFriends()
                    ->where('friends.to', $id)
                    ->orWhere('friends.from', $id);
    }


    public function acceptedFriends()
    {
        return $this->allFriends()
                    ->with('profilePhoto.image')
                    ->where('friends.accepted', true)
                    ->orderBy('friends.opened', 'desc');
    }

    public function pending()
    {
        return $this->allFriends()
                    ->with('profilePhoto.image')
                    ->where('friends.accepted', false)
                    ->orderBy('friends.opened', 'desc');
    }
}
