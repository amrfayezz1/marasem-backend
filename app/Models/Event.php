<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date_start',
        'date_end',
        'time_start',
        'time_end',
        'location',
        'location_url',
        'cover_img_path',
        'status',
        'expires',
    ];

    public function translations()
    {
        return $this->hasMany(EventTranslation::class);
    }
}
