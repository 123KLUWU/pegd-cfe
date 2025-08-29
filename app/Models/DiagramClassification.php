<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagramClassification extends Model
{
    public function diagrams()
    {
        return $this->hasMany(Diagram::class, 'classification_id');
    }
}
