<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtworkTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['artwork_id', 'language_id', 'name', 'art_type', 'description'];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }
}
