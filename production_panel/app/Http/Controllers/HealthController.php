<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Perform health checks.
     */
    public function check(): JsonResponse
    {
        $status = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'queue'    => $this->checkQueue(),
        ];

        $overallStatus = collect($status)->every(fn($s) => $s['status'] === 'ok') ? 200 : 503;

        return response()->json([
            'status' => $overallStatus === 200 ? 'healthy' : 'unhealthy',
            'checks' => $status,
            'timestamp' => now()->toIso8601String(),
        ], $overallStatus);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Redis connection failed'];
        }
    }

    private function checkQueue(): array
    {
        try {
            $size = Queue::size();
            $failed = DB::table('failed_jobs')->count();
            return [
                'status' => 'ok',
                'size' => $size,
                'failed_count' => $failed,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue check failed'];
        }
    }
}
