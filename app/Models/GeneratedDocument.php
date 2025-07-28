<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; // Use SoftDeletes trait

    protected $fillable = [
        'google_drive_id',
        'user_id',
        'template_id',
        'title',
        'type',
        'visibility_status',
        'generated_at',
        'make_private_at',
        'data_values_json', // The JSON data used for this specific document
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'make_private_at' => 'datetime',
        'data_values_json' => 'array', // Cast to array for automatic JSON handling
        'deleted_at' => 'datetime',
        'visibility_status' => 'string', // Or an Enum type if you define it
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con la plantilla utilizada
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    // Configuración para spatie/laravel-activitylog
    public function getActivitylogOptions(): LogOptions // Asegúrate de importar LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Registra los cambios en los campos fillable
            ->logOnlyDirty() // Solo si hay cambios
            ->dontSubmitEmptyLogs(); // No registra si no hay cambios
    }
}