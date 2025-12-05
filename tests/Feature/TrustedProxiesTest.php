<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/proxy-test', function (Request $request) {
        return response()->json([
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
        ]);
    });
});

function bootAppWithProxies(string $value): void
{
    // Configure proxies for this test run
    config()->set('trustedproxy.proxies', $value);

    // Reset any previous state (only needed in tests)
    TrustProxies::at([]);

    // Re-run the provider boot so setupProxies() takes effect
    (new AppServiceProvider(app()))->boot();
}

it('does not trust proxies when config is empty', function () {
    bootAppWithProxies('');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.1', // test client IP
        ])
        ->get('/proxy-test', [
            // Spoofed proxy headers – should be ignored
            'X-Forwarded-For' => '198.51.100.10',
            'X-Forwarded-Proto' => 'https',
        ]);

    $response->assertOk();
    $response->assertJson([
        'ip' => '192.0.2.1', // comes from REMOTE_ADDR, not X-Forwarded-For
        'secure' => false,       // X-Forwarded-Proto ignored
    ]);
});

it('trusts all proxies when config is star', function () {
    bootAppWithProxies('*');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.1', // proxy IP
        ])
        ->get('/proxy-test', [
            'X-Forwarded-For' => '198.51.100.10', // real client IP
            'X-Forwarded-Proto' => 'https',
        ]);

    $response->assertOk();
    $response->assertJson([
        'ip' => '198.51.100.10', // taken from X-Forwarded-For now
        'secure' => true,            // taken from X-Forwarded-Proto
    ]);
});

it('trusts only the configured proxies from comma separated list', function () {
    // Only 192.0.2.1 is a trusted proxy
    bootAppWithProxies('192.0.2.1,10.0.0.0/8');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.1', // trusted proxy
        ])
        ->get('/proxy-test', [
            'X-Forwarded-For' => '198.51.100.10',
            'X-Forwarded-Proto' => 'https',
        ]);

    $response->assertOk();
    $response->assertJson([
        'ip' => '198.51.100.10',
        'secure' => true,
    ]);
});

it('ignores forwarded headers if the remote address is not a trusted proxy', function () {
    // 203.0.113.99 is NOT in the trusted proxy list
    bootAppWithProxies('192.0.2.1,10.0.0.0/8');

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.99', // untrusted proxy
        ])
        ->get('/proxy-test', [
            'X-Forwarded-For' => '198.51.100.10',
            'X-Forwarded-Proto' => 'https',
        ]);

    $response->assertOk();
    $response->assertJson([
        'ip' => '203.0.113.99', // stays as REMOTE_ADDR
        'secure' => false,      // X-Forwarded-Proto ignored
    ]);
});
