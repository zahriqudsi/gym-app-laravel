<?php

namespace App\Providers;

use App\Support\Sms\LogSmsGateway;
use App\Support\Sms\NotifyLkSmsGateway;
use App\Support\Sms\SmsGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, function () {
            return match (config('gym.sms.driver')) {
                'notifylk' => new NotifyLkSmsGateway,
                default => new LogSmsGateway,
            };
        });
    }

    public function boot(): void
    {
        // Catch typos in mass-assignment / attribute names during development.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }
}
