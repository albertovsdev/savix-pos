<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleRondaPedido extends Model
{
    protected $table = 'detalles_rondas_pedidos';
    protected $primaryKey = 'id_detalle_ronda_pedido';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_ronda_pedido', 'ref_producto', 'cantidad', 'nombre_producto', 'precio_unitario', 'importe_extras', 'importe_total', 'nota_preparacion', 'estado', 'activo'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3', 'precio_unitario' => 'decimal:2', 'importe_extras' => 'decimal:2', 'importe_total' => 'decimal:2', 'activo' => 'boolean'];
    }
}
