<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Template extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; // Use SoftDeletes trait

    protected $fillable = [
        'name',
        'google_drive_id',
        'type',
        'category_id',
        'mapping_rules_json',
        'pdf_file_path',
        'description',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'mapping_rules_json' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relación con la Categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relación con el Usuario que la creó
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Relación con los datos prellenados asociados
    public function prefilledData()
    {
        return $this->hasMany(TemplatePrefilledData::class);
    }

    // Relación con los documentos generados a partir de esta plantilla
    public function generatedDocuments()
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    // Configuración para spatie/laravel-activitylog
    public function getActivitylogOptions(): LogOptions // Asegúrate de importar LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}