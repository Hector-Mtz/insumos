<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class entrada extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorias_producto_id',
        'fecha',
        'hora',
        'cantidad'
    ];
}
