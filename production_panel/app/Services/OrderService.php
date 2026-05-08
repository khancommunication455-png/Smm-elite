<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Jobs\ProcessOrderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create a new order.
     *
     * @param array $data
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $data): Order
    {
        $service = Service::findOrFail($data['service_id']);
        $quantity = $data['quantity'];
        $link = $data['link'];
        $total = round(($quantity / 1000) * $service->rate, 6);

        return DB::transaction(function () use ($service, $quantity, $link, $total) {
            /** @var User $user */
            $user = User::lockForUpdate()->find(Auth::id());

            if ($user->funds < $total) {
                throw new \Exception('Insufficient balance. Please add funds first.');
            }

            // Deduct funds
            $user->decrement('funds', $total);

            // Create Order
            $order = Order::create([
                'user_id'    => $user->id,
                'service_id' => $service->id,
                'link'       => $link,
                'quantity'   => $quantity,
                'total'      => $total,
                'status'     => 'pending',
                'remains'    => $quantity,
            ]);

            // Record Transaction
            Transaction::create([
                'user_id'     => $user->id,
                'amount'      => $total,
                'type'        => 'deduction',
                'description' => "Order #{$order->id} – {$service->name}",
                'status'      => 'completed',
                'reference'   => (string) $order->id,
            ]);

            // Dispatch job for API submission if applicable
            if ($service->api_provider_id && $service->api_service_id) {
                ProcessOrderJob::dispatch($order);
            }

            return $order;
        });
    }

    /**
     * Check for duplicate orders within a short cooldown period.
     *
     * @param int $userId
     * @param int $serviceId
     * @param string $link
     * @param int $seconds
     * @return bool
     */
    public function isDuplicateOrder(int $userId, int $serviceId, string $link, int $seconds = 60): bool
    {
        return Order::where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->where('link', $link)
            ->where('created_at', '>=', now()->subSeconds($seconds))
            ->exists();
    }
}
