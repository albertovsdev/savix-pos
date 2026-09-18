<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpcionModificadorProducto extends Model
{
    protected $table = 'opciones_modificadores_productos';
    protected $primaryKey = 'id_opcion_modificador_producto';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_grupo_modificador_producto', 'ref_insumo', 'nombre', 'tipo_modificacion', 'cantidad_insumo', 'precio_adicional', 'activo'];

    protected function casts(): array
    {
        return ['cantidad_insumo' => 'decimal:4', 'precio_adicional' => 'decimal:2', 'activo' => 'boolean'];
    }
}
