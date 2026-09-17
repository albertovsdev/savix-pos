<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_negocio', 'ref_categoria_producto', 'codigo', 'nombre', 'descripcion', 'precio_compra', 'precio_venta', 'tipo_inventario', 'ruta_imagen', 'activo'];

    protected function casts(): array
    {
        return ['precio_compra' => 'decimal:2', 'precio_venta' => 'decimal:2', 'activo' => 'boolean'];
    }
}
