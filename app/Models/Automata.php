<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automata extends Model
{
    protected $table = 'automatas';
    public function diagrams()
    {
        return $this->hasMany(Diagram::class, 'automata_id');
    }
}
