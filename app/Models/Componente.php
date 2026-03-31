<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Componente extends Model
{
    use HasFactory;

    protected $table = 'componentes';

    protected $fillable = [
        'sistema_id',
        'nombre',
    ];

    public function sistema()
    {
        return $this->belongsTo(Sistema::class, 'sistema_id');
    }
}
