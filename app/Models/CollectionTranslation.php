<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['collection_id', 'language_id', 'title'];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}

