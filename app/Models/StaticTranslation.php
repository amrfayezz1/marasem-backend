<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'language_id', 'translation'];

    // Relationships
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
