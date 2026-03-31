<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sistema extends Model
{
    use HasFactory;

    protected $table = 'sistemas';

    protected $fillable = [
        'nombre',
    ];

    public function componentes()
    {
        return $this->hasMany(Componente::class, 'sistema_id');
    }

}
