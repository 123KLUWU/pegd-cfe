<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // For soft deletes

class TemplatePrefilledData extends Model
{
    use HasFactory, SoftDeletes; // Use SoftDeletes trait

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'data_json',
        'is_default_option',
        'tag_id', // Add if you have this FK column in migration
        'unidad_id', // Add if you have this FK column in migration
        'sistema_id', // Add if you have this FK column in migration
        'servicio_id', // Add if you have this FK column in migration
        'created_by_user_id',
    ];

    // Cast 'data_json' to array
    protected $casts = [
        'data_json' => 'array',
        'is_default_option' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationship to Template
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    // Relationship to User who created it
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Relationships to generic data tables (if you added those FKs in the migration)
    public function tag()
    {
        return $this->belongsTo(\App\Models\Tag::class); // Assuming App\Models\Tag exists
    }

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class); // Assuming App\Models\Unidad exists
    }

    public function sistema()
    {
        return $this->belongsTo(\App\Models\Sistema::class); // Assuming App\Models\Sistema exists
    }

    public function servicio()
    {
        return $this->belongsTo(\App\Models\Servicio::class); // Assuming App\Models\Servicio exists
    }
}