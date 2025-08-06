<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tag extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'tags'; // Apunta a la tabla 'tags'
    protected $fillable = [
        'tag', // Sigue siendo 'tag' como el identificador único
        'unidad_id', // Nuevo campo
        'last_calibration_date', // Nuevo campo
        'description', // Nuevo campo
        /*
        'model',
        'serial_number',
        */
    ];

    protected $casts = [
        'last_calibration_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

     // Relación con la Unidad a la que pertenece
     public function unidad()
     {
         return $this->belongsTo(Unidad::class); // Asume que tienes un modelo Unidad
     }
 
     // Relación con los documentos generados para este instrumento
     public function generatedDocuments()
     {
         return $this->hasMany(GeneratedDocument::class, 'instrumento_tag_id'); // Asegura la FK
     }
 
     // Configuración para spatie/laravel-activitylog (si lo usas)
     public function getActivitylogOptions(): LogOptions
     {
         return LogOptions::defaults()
             ->logFillable()
             ->logOnlyDirty()
             ->dontSubmitEmptyLogs();
     }

}
