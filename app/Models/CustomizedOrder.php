<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizedOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'artwork_id',
        'desired_size',
        'offering_price',
        'address_id',
        'description',
        'status',
    ];

    public function translations()
    {
        return $this->hasMany(CustomizedOrderTranslation::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
