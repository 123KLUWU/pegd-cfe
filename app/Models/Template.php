<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // For soft deletes

class Template extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes trait

    protected $fillable = [
        'name',
        'google_drive_id',
        'type',
        'mapping_rules_json',
        'description',
        'is_active',
        'created_by_user_id',
    ];

    // Cast 'mapping_rules_json' to array so Laravel handles JSON encoding/decoding automatically
    protected $casts = [
        'mapping_rules_json' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime', // Optional, but good practice for soft deletes
    ];

    // Relationship to TemplatePrefilledData
    public function prefilledData()
    {
        return $this->hasMany(TemplatePrefilledData::class);
    }

    // Relationship to User who created it
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}