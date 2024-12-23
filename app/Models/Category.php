<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the tags associated with the category.
     */
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}
