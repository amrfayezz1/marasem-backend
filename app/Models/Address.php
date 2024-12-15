<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'zone',
        'address',
        'phone',
        'country_code',
        'name',
        'is_default'
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
