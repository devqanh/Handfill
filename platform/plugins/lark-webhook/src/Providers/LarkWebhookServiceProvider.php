<?php

namespace Botble\LarkWebhook\Providers;

use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\LarkWebhook\Supports\LarkWebhookSupport;
use Botble\Setting\PanelSections\SettingOthersPanelSection;

class LarkWebhookServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/lark-webhook')
            ->loadAndPublishConfigurations('permissions')
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations()
            ->publishAssets();

        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-lark-webhook',
                    'priority' => 900,
                    'parent_id' => null,
                    'name' => 'plugins/lark-webhook::lark-webhook.name',
                    'url' => fn () => route('lark-webhook.index'),
                    'icon' => 'ti ti-webhook',
                    'permissions' => ['lark-webhook.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-lark-webhook-events',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-lark-webhook',
                    'name' => 'plugins/lark-webhook::lark-webhook.events',
                    'url' => fn () => route('lark-webhook.index'),
                    'icon' => null,
                    'permissions' => ['lark-webhook.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-lark-webhook-settings',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-lark-webhook',
                    'name' => 'plugins/lark-webhook::lark-webhook.settings.name',
                    'url' => fn () => route('lark-webhook.settings'),
                    'icon' => null,
                    'permissions' => ['lark-webhook.settings'],
                ]);
        });

        PanelSectionManager::beforeRendering(function (): void {
            PanelSectionManager::default()
                ->registerItem(
                    SettingOthersPanelSection::class,
                    fn () => PanelSectionItem::make('lark-webhook-settings')
                        ->setTitle(trans('plugins/lark-webhook::lark-webhook.settings.name'))
                        ->withIcon('ti ti-webhook')
                        ->withDescription(trans('plugins/lark-webhook::lark-webhook.settings.description'))
                        ->withPriority(120)
                        ->withRoute('lark-webhook.settings')
                );
        });
    }
}
