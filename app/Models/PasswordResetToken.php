<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordResetToken extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['email', 'token', 'expired_at', 'type'];
}
