<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizedOrderTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['customized_order_id', 'language_id', 'description'];

    public function customizedOrder()
    {
        return $this->belongsTo(CustomizedOrder::class);
    }
}
