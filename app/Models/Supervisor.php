<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supervisor extends Model
{
        /** @use HasFactory<\Database\Factories\UserFactory> */
        use HasFactory, SoftDeletes;
        /**
         * The attributes that are mass assignable.
         *
         * @var list<string>
         */
        protected $table = 'supervisores';

        protected $fillable = [
            'name',
            'email',
        ];
        protected $casts = [
            'deleted_at' => 'datetime'
        ];
}
