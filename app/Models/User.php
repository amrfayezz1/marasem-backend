<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'country_code',
        'profile_picture',
        'preferred_currency',
        'preferred_language',
        'marasem_credit',
        'is_artist',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
    public function mainAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'user_tags');
    }

    public function artistDetails()
    {
        return $this->hasOne(ArtistDetail::class);
    }

    public function artworks()
    {
        return $this->hasMany(Artwork::class, 'artist_id');
    }

    public function applyCredit($amount)
    {
        if ($this->marasem_credit >= $amount) {
            $this->marasem_credit -= $amount;
            $this->save();
            return $amount;
        }

        $remaining = $this->marasem_credit;
        $this->marasem_credit = 0;
        $this->save();
        return $remaining;
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'artist_id', 'user_id');
    }

    public function isFollowedBy(User $user)
    {
        return $this->followers()->where('user_id', $user->id)->exists();
    }

    public function getIsFollowedAttribute()
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return false;
        }

        // Check if the logged-in user follows this artist
        return $this->followers()->where('user_id', $user->id)->exists();
    }

    public function follows()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'user_id', 'artist_id');
    }

    public function translations()
    {
        return $this->hasMany(UserTranslation::class);
    }

    public function pickupLocations()
    {
        return $this->hasMany(ArtistPickupLocation::class, 'artist_id');
    }
}
