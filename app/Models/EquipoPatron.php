<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // Para la eliminación lógica
use Spatie\Activitylog\Traits\LogsActivity; // Para el registro de actividad
use Spatie\Activitylog\LogOptions; // Para configurar el log de actividad

class EquipoPatron extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; // Usar SoftDeletes y LogsActivity

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'equipos_patrones'; // Nombre explícito de la tabla

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identificador',
        'descripcion',
        'marca',
        'modelo',
        'numero_serie',
        'ultima_calibracion',
        'proxima_calibracion',
        'vigente',
        'created_by_user_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ultima_calibracion' => 'date',
        'proxima_calibracion' => 'date',
        'vigente' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relación con el usuario que creó el registro
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function generatedDocuments()
    {
        return $this->hasMany(GeneratedDocument::class, 'equipo_patron_id');
    }
    
    // Configuración para spatie/laravel-activitylog
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Registra los cambios en los campos fillable
            ->logOnlyDirty() // Solo si los valores cambiaron
            ->dontSubmitEmptyLogs(); // No registra si no hay cambios
    }
    
}
