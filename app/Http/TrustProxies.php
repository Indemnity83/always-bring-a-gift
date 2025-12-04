<?php

namespace App\Http;

class TrustProxies extends \Illuminate\Http\Middleware\TrustProxies
{
    public function __construct()
    {
        // Set the proxies based on the environment
        $this->proxies = config('app.trusted_proxies');
    }
}
