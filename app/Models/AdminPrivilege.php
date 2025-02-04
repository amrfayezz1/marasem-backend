<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPrivilege extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'privileges'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}