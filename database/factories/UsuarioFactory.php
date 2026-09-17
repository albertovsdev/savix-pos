<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;
    protected static ?string $contrasena;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'nombre_usuario' => fake()->unique()->userName(),
            'correo' => fake()->unique()->safeEmail(),
            'correo_verificado_en' => now(),
            'contrasena' => static::$contrasena ??= Hash::make('contrasena'),
            'token_recordar' => Str::random(10),
            'activo' => true,
        ];
    }

    public function correoNoVerificado(): static
    {
        return $this->state(fn (array $atributos) => ['correo_verificado_en' => null]);
    }
}
