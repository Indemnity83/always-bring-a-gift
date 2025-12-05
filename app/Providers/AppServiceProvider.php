<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Authentik\Provider as AuthentikProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listen for Socialite OAuth calls and configure Authentik provider
        $this->app['events']->listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', AuthentikProvider::class);
        });

        // Define admin gate
        Gate::define('viewAdmin', function ($user) {
            return $user->is_admin === true;
        });

        $this->setupProxies();
    }

    private function setupProxies()
    {
        TrustProxies::withHeaders(config('trustedproxy.headers'));

        $proxies = config('trustedproxy.proxies');

        if ($proxies === '') {
            return; // trust none
        }

        if ($proxies === '*') {
            TrustProxies::at('*');

            return;
        }

        TrustProxies::at(collect(explode(',', $proxies))
            ->map('trim')
            ->filter()
            ->all());
    }
}
