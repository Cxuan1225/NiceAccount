<?php

namespace App\Providers;

use App\Support\AuditTrail;
use Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        Gate::before(function ($user) {
            return $user && $user->isSuperAdmin() ? true : null;
        });

        $ignoreTables = config('audit.ignore_tables', []);

        Event::listen('eloquent.created: *', function ($eventName, $data) use ($ignoreTables) {
            $model = $data[0] ?? null;
            if (!$model)
                return;
            if (in_array($model->getTable(), $ignoreTables, true))
                return;

            AuditTrail::fromModel($model, 'INSERT');
        });

        Event::listen('eloquent.updated: *', function ($eventName, $data) use ($ignoreTables) {
            $model = $data[0] ?? null;
            if (!$model)
                return;
            if (in_array($model->getTable(), $ignoreTables, true))
                return;

            AuditTrail::fromModel($model, 'UPDATE');
        });

        Event::listen('eloquent.deleted: *', function ($eventName, $data) use ($ignoreTables) {
            $model = $data[0] ?? null;
            if (!$model)
                return;
            if (in_array($model->getTable(), $ignoreTables, true))
                return;

            AuditTrail::fromModel($model, 'DELETE');
        });
    }
}
