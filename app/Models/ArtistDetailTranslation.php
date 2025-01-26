<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistDetailTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['artist_detail_id', 'language_id', 'summary'];

    public function artistDetail()
    {
        return $this->belongsTo(ArtistDetail::class);
    }
}

