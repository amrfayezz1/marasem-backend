<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'super_category_id'];

    /**
     * Get the super category for this category.
     */
    public function superCategory()
    {
        return $this->belongsTo(Category::class, 'super_category_id');
    }

    /**
     * Get the subcategories for this category.
     */
    public function subCategories()
    {
        return $this->hasMany(Category::class, 'super_category_id');
    }
}
