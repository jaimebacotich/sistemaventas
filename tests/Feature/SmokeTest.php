<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    /**
     * Prueba básica de disponibilidad (Smoke Test).
     * Usa endpoint /api/health para evitar dependencia de Vite manifest en CI.
     */
    public function test_application_is_alive(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    /**
     * Prueba de salud del sistema.
     */
    public function test_health_check_endpoint_works(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'app', 'db']);
    }
}
