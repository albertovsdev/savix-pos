<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_instalacion_sin_configurar_redirige_a_configuracion_inicial(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/configuracion-inicial');
    }
}
