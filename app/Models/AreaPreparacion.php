<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaPreparacion extends Model
{
    protected $table = 'areas_preparacion';
    protected $primaryKey = 'id_area_preparacion';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_sucursal', 'nombre', 'codigo', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
