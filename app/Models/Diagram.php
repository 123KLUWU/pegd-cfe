<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagram extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'file_original_name',
        'type',
        'machine_category',
        'description',
        'is_active',
        'created_by_user_id'
    ];
    protected $dates = [
        'deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationship to the User who generated it
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
