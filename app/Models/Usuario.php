<?php

namespace App\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['nombre', 'nombre_usuario', 'correo', 'contrasena', 'pin_hash', 'activo'];
    protected $hidden = ['contrasena', 'pin_hash', 'token_recordar'];

    protected function casts(): array
    {
        return ['correo_verificado_en' => 'datetime', 'contrasena' => 'hashed', 'activo' => 'boolean'];
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordar';
    }
}
