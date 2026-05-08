<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * VerifyCsrfToken
 *
 * FIXES LOW-3: Only the webhook endpoints are excluded from CSRF, not the entire application.
 *
 * Stripe and PayPal send POST requests from their servers with no session/cookie,
 * so CSRF verification must be disabled for these paths.
 * All other routes retain full CSRF protection.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/webhooks/stripe',
        'api/webhooks/paypal',
    ];
}
