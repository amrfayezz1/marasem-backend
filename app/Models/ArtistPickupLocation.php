<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistPickupLocation extends Model
{
    use HasFactory;

    protected $table = 'artists_pickup_locations';

    protected $fillable = [
        'artist_id',
        'city',
        'zone',
        'address',
    ];

    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}

