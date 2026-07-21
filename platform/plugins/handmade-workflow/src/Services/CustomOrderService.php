<?php

namespace Botble\HandmadeWorkflow\Services;

use Botble\Ecommerce\Enums\OrderAddressTypeEnum;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Models\Address;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\Media\Facades\RvMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomOrderService
{
    public const MEDIA_FOLDER = 'handmade-custom-orders';

    public const META_CUSTOMER_GROUP = 'handmade_customer_group';

    public const META_EXPECTED_DATE = 'handmade_expected_date';

    public const META_IS_CUSTOM = 'handmade_is_custom_order';

    /**
     * Turn a customer's made-to-order request into an Order sitting at step 1
     * (Chờ duyệt). Price stays 0 until HF quotes it in phase 3.
     *
     * @param  array{customer_group: string, expected_date?: string|null, note?: string|null, address_id?: int|string|null, items: array<int, array<string, mixed>>}  $data
     */
    public function create(Customer $customer, array $data): Order
    {
        // Upload outside the transaction: file writes cannot be rolled back anyway,
        // and we want a failed upload to abort before any DB row is created.
        $uploadedItems = [];

        foreach ($data['items'] as $item) {
            $uploadedItems[] = [
                'name' => $item['name'],
                'note' => $item['note'] ?? null,
                'qty' => (int) $item['qty'],
                'marketplace_order_id' => $item['marketplace_order_id'] ?? null,
                'sku' => $item['sku'] ?? null,
                'ordered_at' => $item['ordered_at'] ?? null,
                // Files the customer picked in the browser live on our storage; photos
                // that came in from a sheet stay as the customer's own links.
                'images' => $this->uploadImages($item['images'] ?? []),
                'image_links' => array_values($item['image_links'] ?? []),
                'fabric_links' => array_values($item['fabric_image_links'] ?? []),
                'recipient' => $this->cleanRecipient($item['recipient'] ?? []),
            ];
        }

        return DB::transaction(function () use ($customer, $data, $uploadedItems): Order {
            $order = Order::query()->create([
                'user_id' => $customer->getKey(),
                'status' => OrderStatusEnum::PENDING,
                'amount' => 0,
                'sub_total' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'description' => $data['note'] ?? null,
                // Marks a genuinely placed order. The customer order list filters on this,
                // so without it the request would be invisible to the customer.
                'is_finished' => 1,
            ]);

            // The Order::created hook already puts it at PENDING_APPROVAL; be explicit anyway.
            $order->forceFill([
                'production_status' => ProductionStatusEnum::PENDING_APPROVAL,
                'production_status_updated_at' => now(),
            ])->saveQuietly();

            $order->setOrderMetadata(self::META_IS_CUSTOM, '1');
            $order->setOrderMetadata(self::META_CUSTOMER_GROUP, $data['customer_group']);

            $this->attachShippingAddress($order, $customer, $data['address_id'] ?? null, $uploadedItems);

            if (! empty($data['expected_date'])) {
                $order->setOrderMetadata(self::META_EXPECTED_DATE, $data['expected_date']);
            }

            foreach ($uploadedItems as $item) {
                OrderProduct::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => null, // made-to-order: not tied to a catalogue product
                    'product_name' => $item['name'],
                    // The first photo doubles as the line thumbnail (leaving it empty
                    // makes core draw a placeholder). The gallery below therefore starts
                    // from the second photo so nothing is shown twice.
                    'product_image' => $this->thumbnail($item),
                    'qty' => $item['qty'],
                    'price' => 0, // set when HF quotes the order
                    'tax_amount' => 0,
                    'weight' => 0,
                    'options' => [
                        'handmade' => array_merge(['is_custom' => true], Arr::except($item, ['name', 'qty'])),
                    ],
                ]);
            }

            return $order;
        });
    }

    /**
     * Give the order a delivery address. Normally that is the one picked from the
     * customer's address book; for an imported marketplace sheet there is none, so
     * the first line's own recipient stands in — core screens and the invoice all
     * read this record and would otherwise show a blank.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function attachShippingAddress(
        Order $order,
        Customer $customer,
        int|string|null $addressId,
        array $items
    ): void {
        if ($addressId && $this->copyShippingAddress($order, $customer, $addressId)) {
            return;
        }

        foreach ($items as $item) {
            if (! empty($item['recipient']['name'])) {
                OrderAddress::query()->create([
                    'order_id' => $order->getKey(),
                    'type' => OrderAddressTypeEnum::SHIPPING,
                    'name' => $item['recipient']['name'],
                    'email' => $item['recipient']['email'] ?: $customer->email,
                    'address' => $item['recipient']['address'],
                ]);

                return;
            }
        }
    }

    /**
     * Snapshot the customer's saved address onto the order. It is copied (not referenced)
     * so later edits to the address book do not rewrite a placed order's delivery details.
     */
    protected function copyShippingAddress(Order $order, Customer $customer, int|string $addressId): bool
    {
        $address = Address::query()
            ->where('id', $addressId)
            ->where('customer_id', $customer->getKey())
            ->first();

        if (! $address) {
            return false;
        }

        OrderAddress::query()->create([
            'order_id' => $order->getKey(),
            'type' => OrderAddressTypeEnum::SHIPPING,
            'name' => $address->name,
            'phone' => $address->phone,
            'email' => $address->email ?: $customer->email,
            'country' => $address->country,
            'state' => $address->state,
            'city' => $address->city,
            'address' => $address->address,
            'zip_code' => $address->zip_code,
        ]);

        return true;
    }

    /**
     * The line thumbnail. `RvMedia::getImageUrl()` hands absolute URLs straight back,
     * so a customer's own link renders here just as an uploaded file would — as long
     * as it fits the column, which is varchar(255).
     *
     * @param  array<string, mixed>  $item
     */
    protected function thumbnail(array $item): ?string
    {
        if ($first = $item['images'][0] ?? null) {
            return $first;
        }

        foreach ($item['image_links'] as $link) {
            if (strlen($link) <= 255) {
                return $link;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $recipient
     * @return array{name: string, email: string, address: string}|null
     */
    protected function cleanRecipient(array $recipient): ?array
    {
        $clean = [
            'name' => trim((string) ($recipient['name'] ?? '')),
            'email' => trim((string) ($recipient['email'] ?? '')),
            'address' => trim((string) ($recipient['address'] ?? '')),
        ];

        return array_filter($clean) === [] ? null : $clean;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string> Stored image URLs.
     */
    protected function uploadImages(array $files): array
    {
        $urls = [];

        foreach (array_filter($files) as $file) {
            $result = RvMedia::handleUpload($file, 0, self::MEDIA_FOLDER);

            if (! empty($result['error'])) {
                throw new RuntimeException($result['message'] ?? 'Upload failed.');
            }

            $urls[] = $result['data']->url;
        }

        return $urls;
    }

    public static function isCustomOrder(Order $order): bool
    {
        return (bool) $order->getOrderMetadata(self::META_IS_CUSTOM);
    }

    /**
     * Custom items attached to an order, ready for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function customItems(Order $order): array
    {
        $items = [];

        foreach ($order->products as $product) {
            $handmade = data_get($product->options, 'handmade');

            if (! $handmade || empty($handmade['is_custom'])) {
                continue;
            }

            $items[] = array_merge($handmade, [
                'name' => $product->product_name,
                'qty' => (int) $product->qty,
                'images' => $handmade['images'] ?? [],
            ]);
        }

        return $items;
    }
}
