<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_funds()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/funds/add', [
            'amount' => 10.00,
            'reference' => 'test-ref-123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 10.00,
            'reference' => 'test-ref-123',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_reference_prevention()
    {
        $user = User::factory()->create();
        Transaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'reference' => 'duplicate-ref',
            'status' => 'completed',
            'type' => 'deposit',
        ]);

        $response = $this->actingAs($user)->post('/funds/add', [
            'amount' => 5.00,
            'reference' => 'duplicate-ref',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reference');
    }
}