<?php

namespace Botble\LarkWebhook\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\LarkWebhook\Models\LarkWebhookEvent;
use Botble\LarkWebhook\Supports\LarkWebhookSupport;
use Botble\LarkWebhook\Tables\LarkWebhookEventTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class LarkWebhookController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/lark-webhook::lark-webhook.name'), route('lark-webhook.index'));
    }

    public function index(LarkWebhookEventTable $table): View|JsonResponse
    {
        $this->pageTitle(trans('plugins/lark-webhook::lark-webhook.name'));

        return $table->renderTable([], ['webhookUrl' => LarkWebhookSupport::webhookUrl()]);
    }

    public function show(int $event): View
    {
        $event = LarkWebhookEvent::query()->findOrFail($event);

        $this->pageTitle(trans('plugins/lark-webhook::lark-webhook.event_detail', ['id' => $event->getKey()]));

        return view('plugins/lark-webhook::show', compact('event'));
    }

    public function destroy(int $event): DeleteResourceAction
    {
        return DeleteResourceAction::make(LarkWebhookEvent::query()->findOrFail($event));
    }

    public function empty(): BaseHttpResponse
    {
        LarkWebhookEvent::query()->truncate();

        return $this->httpResponse()->withDeletedSuccessMessage();
    }

    public function regenerateToken(): BaseHttpResponse
    {
        LarkWebhookSupport::regenerateToken();

        return $this->httpResponse()
            ->setNextUrl(route('lark-webhook.index'))
            ->setMessage(trans('plugins/lark-webhook::lark-webhook.token_regenerated'));
    }
}
