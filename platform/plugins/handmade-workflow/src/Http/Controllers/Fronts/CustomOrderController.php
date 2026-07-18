<?php

namespace Botble\HandmadeWorkflow\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Address;
use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
use Botble\HandmadeWorkflow\Http\Requests\CreateCustomOrderRequest;
use Botble\HandmadeWorkflow\Services\CustomOrderService;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomOrderController extends Controller
{
    public function __construct(protected CustomOrderService $service)
    {
        // Same asset pair every customer-account controller registers. customer.css is
        // what styles the account shell (sidebar card, avatar size, profile layout) —
        // front-ecommerce.css alone is not enough.
        $version = EcommerceHelper::getAssetVersion();

        Theme::asset()
            ->add('customer-style', 'vendor/core/plugins/ecommerce/css/customer.css', ['bootstrap-css'], version: $version);
        Theme::asset()
            ->add('front-ecommerce-css', 'vendor/core/plugins/ecommerce/css/front-ecommerce.css', version: $version);
    }

    public function create()
    {
        $title = trans('plugins/handmade-workflow::handmade-workflow.custom_order.title');

        // Loads front-ecommerce.css/js — without it the customer dashboard shell
        // (sidebar, avatar sizing, profile layout) renders unstyled.
        EcommerceHelper::registerThemeAssets();

        SeoHelper::setTitle($title);

        Theme::breadcrumb()->add($title, route('customer.custom-orders.create'));

        // Front-end pages must go through Theme::scope so the theme layout (header,
        // footer, assets) wraps them and the theme can override the view.
        return Theme::scope(
            'handmade-workflow.custom-order-form',
            [
                'customerGroups' => CustomerGroupEnum::labels(),
                'maxItems' => CreateCustomOrderRequest::MAX_ITEMS,
                'maxImages' => CreateCustomOrderRequest::MAX_IMAGES_PER_ITEM,
                'addresses' => Address::query()
                    ->where('customer_id', Auth::guard('customer')->id())
                    ->orderByDesc('is_default')
                    ->get(),
            ],
            'plugins/handmade-workflow::customer.custom-order-form'
        )->render();
    }

    public function store(CreateCustomOrderRequest $request, BaseHttpResponse $response)
    {
        $items = [];

        foreach ($request->input('items', []) as $index => $item) {
            $items[] = [
                'name' => $item['name'],
                'note' => $item['note'] ?? null,
                'qty' => $item['qty'],
                'images' => $request->file("items.{$index}.images", []),
            ];
        }

        try {
            $order = $this->service->create(Auth::guard('customer')->user(), [
                'customer_group' => $request->input('customer_group'),
                'expected_date' => $request->input('expected_date'),
                'note' => $request->input('note'),
                'address_id' => $request->input('address_id'),
                'items' => $items,
            ]);
        } catch (Throwable $e) {
            Log::error('Custom order creation failed', ['exception' => $e]);

            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.custom_order.create_failed'));
        }

        return $response
            ->setNextUrl(route('customer.orders.view', $order->getKey()))
            ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.custom_order.created'));
    }
}
