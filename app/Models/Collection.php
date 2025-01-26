<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'tags', 'followers'];

    public function translations()
    {
        return $this->hasMany(CollectionTranslation::class);
    }
    /**
     * Many-to-Many relationship: Collection contains many Artworks.
     */
    public function artworks()
    {
        return $this->belongsToMany(Artwork::class, 'artwork_collection');
    }

    /**
     * Convert tags to an array.
     */
    public function getTagsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Set tags as a JSON string.
     */
    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = json_encode($value);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'collection_user');
    }

}
