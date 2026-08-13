<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'razorpaypayment',
        'admin/orders/renewComplete',
        'admin/orders/loginFreerenewComplete',
        'admin/orders/addTopUpComplete',
    ];
}
