<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    protected $fillable = ['refresh_token','access_token','access_token_expires_at'];
    protected $casts = ['access_token_expires_at' => 'datetime'];
}
