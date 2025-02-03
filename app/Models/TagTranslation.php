<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['tag_id', 'language_id', 'name'];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
