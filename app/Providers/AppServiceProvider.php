<?php

namespace App\Providers;

use App\Services\WebhookValidationService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        $this->app->singleton(WebhookValidationService::class, function ($app) {
            return new WebhookValidationService;
        });
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

        $this->configureMailFromDatabase();

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

    /**
     * Apply mail settings stored in database to runtime config.
     */
    private function configureMailFromDatabase(): void
    {
        // Skip if database is not available or tables don't exist
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }
        } catch (\Exception $e) {
            // Database not available, skip configuration
            return;
        }

        try {
            $settings = \App\Models\MailSetting::query()->latest()->first();

            if (! $settings) {
                return;
            }

            $scheme = $settings->encryption === 'ssl' ? 'smtps' : 'smtp';

            Config::set('mail.default', $settings->driver ?: config('mail.default'));
            Config::set('mail.mailers.smtp.scheme', $scheme);
            Config::set('mail.mailers.smtp.host', $settings->host ?: config('mail.mailers.smtp.host'));
            Config::set('mail.mailers.smtp.port', $settings->port ?: config('mail.mailers.smtp.port'));
            Config::set('mail.mailers.smtp.username', $settings->username ?: config('mail.mailers.smtp.username'));
            Config::set('mail.mailers.smtp.password', $settings->password ?: config('mail.mailers.smtp.password'));
            Config::set('mail.from.address', $settings->from_address ?: config('mail.from.address'));
            Config::set('mail.from.name', $settings->from_name ?: config('mail.from.name'));
        } catch (\Exception $e) {
            // Failed to get settings, skip configuration
            return;
        }
    }
}
