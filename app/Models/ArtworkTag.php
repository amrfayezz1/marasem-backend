<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkTag extends Model
{
    protected $table = 'artwork_tag';
    protected $fillable = ['artwork_id', 'tag_id'];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
