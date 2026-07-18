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
     * @param  array{customer_group: string, expected_date?: string|null, note?: string|null, items: array<int, array{name: string, note?: string|null, qty: int, images?: array<int, UploadedFile>|null}>}  $data
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
                'marketplace_id' => $item['marketplace_id'] ?? null,
                'images' => $this->uploadImages($item['images'] ?? []),
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

            $this->copyShippingAddress($order, $customer, $data['address_id'] ?? null);

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
                    'product_image' => $item['images'][0] ?? null,
                    'qty' => $item['qty'],
                    'price' => 0, // set when HF quotes the order
                    'tax_amount' => 0,
                    'weight' => 0,
                    'options' => [
                        'handmade' => [
                            'is_custom' => true,
                            'note' => $item['note'],
                            'marketplace_id' => $item['marketplace_id'] ?? null,
                            'images' => $item['images'],
                        ],
                    ],
                ]);
            }

            return $order;
        });
    }

    /**
     * Snapshot the customer's saved address onto the order. It is copied (not referenced)
     * so later edits to the address book do not rewrite a placed order's delivery details.
     */
    protected function copyShippingAddress(Order $order, Customer $customer, int|string|null $addressId): void
    {
        if (! $addressId) {
            return;
        }

        $address = Address::query()
            ->where('id', $addressId)
            ->where('customer_id', $customer->getKey())
            ->first();

        if (! $address) {
            return;
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
     * @return array<int, array{name: string, note: ?string, qty: int, images: array<int, string>}>
     */
    public static function customItems(Order $order): array
    {
        $items = [];

        foreach ($order->products as $product) {
            $handmade = data_get($product->options, 'handmade');

            if (! $handmade || empty($handmade['is_custom'])) {
                continue;
            }

            $items[] = [
                'name' => $product->product_name,
                'note' => $handmade['note'] ?? null,
                'qty' => (int) $product->qty,
                'images' => $handmade['images'] ?? [],
            ];
        }

        return $items;
    }
}
