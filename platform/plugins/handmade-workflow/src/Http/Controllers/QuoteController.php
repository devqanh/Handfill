<?php

namespace Botble\HandmadeWorkflow\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Order;
use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\HandmadeWorkflow\Http\Requests\SaveQuoteRequest;
use Botble\HandmadeWorkflow\Services\ProductionWorkflow;
use Botble\HandmadeWorkflow\Services\QuoteService;

class QuoteController extends BaseController
{
    public function __construct(
        protected QuoteService $quotes,
        protected ProductionWorkflow $workflow
    ) {
    }

    public function store(Order $order, SaveQuoteRequest $request): BaseHttpResponse
    {
        // Re-pricing after the deposit is paid would silently change what the customer agreed to.
        if (! in_array($this->workflow->currentStatus($order), [
            ProductionStatusEnum::PENDING_APPROVAL,
            ProductionStatusEnum::QUOTED,
        ], true)) {
            return $this->httpResponse()
                ->setError()
                ->setCode(422)
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.errors.quote_locked'));
        }

        $data = $request->validated();

        // Line prices drive the product cost; they are saved onto the order items too.
        $data['product_cost'] = $this->quotes->applyItemPrices($order, $data['items']);

        $this->quotes->save($order, $data);

        // Sending a quote moves the order to "Quoted" — the customer's turn to respond.
        if ($this->workflow->currentStatus($order) === ProductionStatusEnum::PENDING_APPROVAL) {
            $this->workflow->transition($order, ProductionStatusEnum::QUOTED);
        }

        return $this->httpResponse()
            ->setPreviousUrl(route('orders.edit', $order->getKey()))
            ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.quote.saved'));
    }
}
