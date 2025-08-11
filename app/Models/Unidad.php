<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use HasFactory; // , SoftDeletes;

    protected $table = 'unidades';
    protected $fillable = [
        'unidad',
        'description',
    ];

    // Relación con los documentos generados en esta unidad
    public function generatedDocuments()
    {
        return $this->hasMany(GeneratedDocument::class, 'unidad_id');
    }
}
