<?php

namespace Botble\HandmadeWorkflow\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Order;
use Botble\HandmadeWorkflow\Http\Requests\UpdateProductionStatusRequest;
use Botble\HandmadeWorkflow\Services\ProductionWorkflow;
use InvalidArgumentException;

class ProductionStatusController extends BaseController
{
    public function __construct(protected ProductionWorkflow $workflow)
    {
    }

    public function update(Order $order, UpdateProductionStatusRequest $request): BaseHttpResponse
    {
        try {
            $this->workflow->transition(
                $order,
                $request->input('production_status'),
                $request->input('note')
            );
        } catch (InvalidArgumentException $e) {
            return $this->httpResponse()
                ->setError()
                ->setCode(422)
                ->setMessage($e->getMessage());
        }

        return $this->httpResponse()
            ->setPreviousUrl(route('orders.edit', $order->getKey()))
            ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.status_updated'));
    }
}
