<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoModificadorProducto extends Model
{
    protected $table = 'grupos_modificadores_productos';
    protected $primaryKey = 'id_grupo_modificador_producto';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_producto', 'nombre', 'minimo_selecciones', 'maximo_selecciones', 'activo'];

    protected function casts(): array
    {
        return ['minimo_selecciones' => 'integer', 'maximo_selecciones' => 'integer', 'activo' => 'boolean'];
    }
}
