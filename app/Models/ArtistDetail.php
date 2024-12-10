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
    ];

    /**
     * Get the user that owns the artist details.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}