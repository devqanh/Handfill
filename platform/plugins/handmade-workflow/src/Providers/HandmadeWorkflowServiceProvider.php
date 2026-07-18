<?php

namespace Botble\HandmadeWorkflow\Providers;

use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\HandmadeWorkflow\Services\ProductionWorkflow;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\View;

class HandmadeWorkflowServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->singleton(ProductionWorkflow::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/handmade-workflow')
            ->loadAndPublishConfigurations('permissions')
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations();

        // Lets this plugin override individual ecommerce views (the customer order list
        // has no per-order hook). Prepending puts our copy ahead of the plugin's own.
        View::prependNamespace(
            'plugins/ecommerce',
            plugin_path('handmade-workflow/resources/views/overrides/ecommerce')
        );

        $this->app->booted(fn (Application $app) => $app->register(HookServiceProvider::class));
    }
}
