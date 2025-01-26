<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category_id'];

    public function translations()
    {
        return $this->hasMany(TagTranslation::class);
    }
    /**
     * Many-to-Many relationship: Tags belong to many Artworks.
     */
    public function artworks()
    {
        return $this->belongsToMany(Artwork::class, 'artwork_tag');
    }

    /**
     * A tag belongs to a category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tags');
    }

}
