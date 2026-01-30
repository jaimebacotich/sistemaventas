<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['estado' => true]);
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_ventas(): void
    {
        Venta::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ventas');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_can_create_venta_api(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['precio_venta' => 100]);

        $payload = [
            'cliente_id' => $cliente->id,
            'tipo_comprobante' => Venta::COMPROBANTE_FACTURA,
            'numero_comprobante' => 'F001-0001',
            'fecha_venta' => now()->toDateString(),
            'tipo_venta' => Venta::TIPO_CONTADO,
            'total' => 200,
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 2,
                    'precio_unitario' => 100,
                ],
            ],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ventas', $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }
}
