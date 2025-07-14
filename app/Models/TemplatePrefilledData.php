<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // For soft deletes

class TemplatePrefilledData extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'data_json',
        'tag_id',
        'unidad_id',
        'sistema_id',
        'servicio_id',
        'created_by_user_id',
    ];
    protected $casts = [
        'data_json' => 'array',
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
        return $this->belongsTo(Tag::class); // Assuming App\Models\Tag exists
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class); // Assuming App\Models\Unidad exists
    }

    public function sistema()
    {
        return $this->belongsTo(Sistema::class); // Assuming App\Models\Sistema exists
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class); // Assuming App\Models\Servicio exists
    }
}