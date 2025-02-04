<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_media_link',
        'portfolio_link',
        'website_link',
        'other_link',
        'summary',
        'registration_step',
        'completed',
        'profile_views',
        'appreciations_count',
        'status'
    ];

    public function translations()
    {
        return $this->hasMany(ArtistDetailTranslation::class);
    }

    /**
     * Get the user that owns the artist details.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pickupLocation()
    {
        return $this->hasOne(ArtistPickupLocation::class, 'artist_id');
    }
}