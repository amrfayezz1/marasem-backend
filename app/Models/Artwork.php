<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'name',
        'photos',
        'art_type',
        'artwork_status',
        'sizes_prices',
        'description',
        'customizable',
        'duration',
        'likes_count',
        'min_price',
        'max_price',
        'reviewed',
    ];

    public function translations()
    {
        return $this->hasMany(ArtworkTranslation::class);
    }

    public function likes()
    {
        return $this->hasMany(ArtworkLike::class);
    }

    /**
     * Many-to-Many relationship: Artwork can have many Tags.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'artwork_tag');
    }

    /**
     * Many-to-Many relationship: Artwork can belong to many Collections.
     */
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'artwork_collection');
    }

    /**
     * Relationship: Artwork belongs to an Artist (User).
     */
    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id')
            ->with([
                'translations' => function ($query) {
                    $locale = auth('sanctum')->user()->preferred_language ?? null;
                    if ($locale) {
                        $query->where('language_id', $locale);
                    }
                }
            ]);
    }


    /**
     * Accessor to convert photos to an array.
     */
    public function getPhotosAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Mutator to store photos as JSON.
     */
    public function setPhotosAttribute($value)
    {
        $this->attributes['photos'] = json_encode($value);
    }

    /**
     * Accessor to convert sizes_prices to an array.
     */
    public function getSizesPricesAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Mutator to store sizes_prices as JSON.
     */
    public function setSizesPricesAttribute($value)
    {
        $this->attributes['sizes_prices'] = json_encode($value);
    }

    public function attachTags(array $tagIds)
    {
        $this->tags()->sync($tagIds); // Sync allows adding/removing efficiently.
    }
    public function attachCollections(array $collectionIds)
    {
        $this->collections()->sync($collectionIds);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, OrderItem::class, 'artwork_id', 'id', 'id', 'order_id');
    }
}