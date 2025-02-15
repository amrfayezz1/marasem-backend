<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'description',
        'meta_keyword',
        'url',
        'picture',
    ];

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }
    /**
     * Get the tags associated with the category.
     */
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function artworks()
    {
        return $this->belongsToMany(Artwork::class, 'category_artworks');
    }

}
