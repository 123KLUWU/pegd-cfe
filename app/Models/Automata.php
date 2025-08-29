<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automata extends Model
{
    public function diagrams()
    {
        return $this->hasMany(Diagram::class, 'automata_id');
    }
}
