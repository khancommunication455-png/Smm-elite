<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_order()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($user)->post('/orders', [
            'service_id' => $service->id,
            'link' => 'https://example.com',
            'quantity' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'quantity' => 100,
        ]);
    }

    public function test_order_validation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/orders', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['service_id', 'link', 'quantity']);
    }
}