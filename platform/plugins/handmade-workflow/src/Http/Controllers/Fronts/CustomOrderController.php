<?php

namespace Botble\HandmadeWorkflow\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Address;
use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
use Botble\HandmadeWorkflow\Http\Requests\CreateCustomOrderRequest;
use Botble\HandmadeWorkflow\Http\Requests\ImportCustomOrderRequest;
use Botble\HandmadeWorkflow\Services\CustomOrderImporter;
use Botble\HandmadeWorkflow\Services\CustomOrderImportException;
use Botble\HandmadeWorkflow\Services\CustomOrderImportSchema;
use Botble\HandmadeWorkflow\Services\CustomOrderService;
use Botble\HandmadeWorkflow\Services\CustomOrderTemplateWriter;
use Botble\HandmadeWorkflow\Services\ImageLinkChecker;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                'importColumns' => collect(CustomOrderImportSchema::columns())
                    ->map(fn (array $column, string $key): array => [
                        'label' => $column['label'],
                        'required' => $column['required'],
                        'description' => CustomOrderImportSchema::description($key),
                    ])
                    ->values()
                    ->all(),
                'addresses' => Address::query()
                    ->where('customer_id', Auth::guard('customer')->id())
                    ->orderByDesc('is_default')
                    ->get(),
            ],
            'plugins/handmade-workflow::customer.custom-order-form'
        )->render();
    }

    /**
     * The blank sheet customers fill in. Generated on the fly so it can never drift
     * from the columns the importer expects.
     */
    public function template(CustomOrderTemplateWriter $writer): BinaryFileResponse
    {
        $path = $writer->store();

        return response()
            ->download($path, $writer->fileName())
            ->deleteFileAfterSend();
    }

    /**
     * Read an uploaded sheet and hand the rows back for the customer to review.
     * Nothing is ordered here — only the photos are fetched, so the preview can
     * show which links resolved to a real image and which did not.
     */
    public function import(
        ImportCustomOrderRequest $request,
        CustomOrderImporter $importer,
        ImageLinkChecker $checker,
        BaseHttpResponse $response
    ) {
        try {
            $result = $importer->parse($request->file('file'));
        } catch (CustomOrderImportException $exception) {
            return $response->setError()->setMessage($exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Custom order import failed', ['exception' => $exception]);

            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.import.errors.unreadable'));
        }

        // Probe the links now so the preview can mark the dead ones while the customer
        // still has the sheet open, instead of failing them at submit.
        $result['link_status'] = $this->checkLinks($result['items'], $checker);

        foreach ($result['link_status'] as $link => $status) {
            if ($status === ImageLinkChecker::BROKEN) {
                $result['warnings'][] = trans('plugins/handmade-workflow::handmade-workflow.import.errors.link_broken', [
                    'link' => Str::limit($link, 70),
                ]);
            }
        }

        return $response
            ->setData($result)
            ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.import.parsed', [
                'count' => count($result['items']),
            ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, string>
     */
    protected function checkLinks(array $items, ImageLinkChecker $checker): array
    {
        $links = [];

        foreach ($items as $item) {
            $links = array_merge($links, $item['image_links'], $item['fabric_image_links']);
        }

        return $links ? $checker->check($links) : [];
    }

    public function store(CreateCustomOrderRequest $request, BaseHttpResponse $response)
    {
        $items = [];

        foreach ($request->input('items', []) as $index => $item) {
            $items[] = [
                'name' => $item['name'],
                'note' => $item['note'] ?? null,
                'qty' => $item['qty'],
                'marketplace_order_id' => $item['marketplace_order_id'] ?? null,
                'sku' => $item['sku'] ?? null,
                'ordered_at' => $item['ordered_at'] ?? null,
                'images' => $request->file("items.{$index}.images", []),
                'image_links' => Arr::wrap($item['image_links'] ?? []),
                'fabric_image_links' => Arr::wrap($item['fabric_image_links'] ?? []),
                'recipient' => [
                    'name' => $item['recipient_name'] ?? null,
                    'email' => $item['recipient_email'] ?? null,
                    'address' => $item['recipient_address'] ?? null,
                ],
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
