<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkCollection extends Model
{
    protected $table = 'artwork_collection';
    protected $fillable = ['artwork_id', 'collection_id'];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
