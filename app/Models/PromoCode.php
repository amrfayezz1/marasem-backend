<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'usages',
        'status',
        'max_usages',
        'expiry_date',
        'discount_type',
        'discount_value',
    ];

    public function isValid(): bool
    {
        return $this->status === 'active' &&
            (!$this->expiry_date || $this->expiry_date > now()) &&
            (!$this->max_usages || $this->usages < $this->max_usages);
    }
}
