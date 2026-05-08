<?php

namespace Tests\Unit;

use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use App\Services\Payments\PaymentLedgerService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ProcessPaymentWebhookTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_paypal_order_approved_event_does_not_credit_balance(): void
    {
        $event = new PaymentWebhookEvent([
            'gateway' => 'paypal',
            'gateway_event_id' => 'WH-APPROVED-1',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'payload' => [
                'resource' => [
                    'id' => 'ORDER-123',
                    'custom_id' => '42',
                    'purchase_units' => [
                        [
                            'amount' => ['value' => '25.00'],
                        ],
                    ],
                ],
            ],
        ]);

        $ledger = Mockery::mock(PaymentLedgerService::class);
        $ledger->shouldNotReceive('creditDeposit');
        $ledger->shouldNotReceive('recordFailure');

        $this->processPayPal($event, $ledger);
    }

    public function test_paypal_capture_completed_event_credits_balance(): void
    {
        $resource = [
            'id' => 'CAPTURE-123',
            'custom_id' => '42',
            'amount' => ['value' => '25.00'],
        ];

        $event = new PaymentWebhookEvent([
            'gateway' => 'paypal',
            'gateway_event_id' => 'WH-CAPTURE-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'payload' => ['resource' => $resource],
        ]);

        $ledger = Mockery::mock(PaymentLedgerService::class);
        $ledger->shouldReceive('creditDeposit')
            ->once()
            ->with('paypal', 42, 25.0, 'CAPTURE-123', $resource);
        $ledger->shouldNotReceive('recordFailure');

        $this->processPayPal($event, $ledger);
    }

    private function processPayPal(PaymentWebhookEvent $event, PaymentLedgerService $ledger): void
    {
        $method = new ReflectionMethod(ProcessPaymentWebhook::class, 'processPayPal');
        $method->setAccessible(true);
        $method->invoke(new ProcessPaymentWebhook(1), $event, $ledger);
    }
}
