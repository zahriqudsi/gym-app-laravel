<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\Sms\LogSmsGateway;
use App\Support\Sms\NotifyLkSmsGateway;
use App\Support\Sms\SmsGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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

        $this->applyStoredSettings();
    }

    /**
     * Overlay admin-editable settings (settings table) on top of config/gym.php,
     * so every config('gym.*') read picks up overrides with no other changes.
     */
    protected function applyStoredSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $values = Setting::allValues();
            if ($values) {
                config($values);
            }
        } catch (\Throwable) {
            // DB not ready (fresh install, CI without DB) — fall back to config defaults.
        }
    }
}
