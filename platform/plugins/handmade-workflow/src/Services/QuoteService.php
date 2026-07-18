<?php

namespace Botble\HandmadeWorkflow\Services;

use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
use Botble\HandmadeWorkflow\Models\OrderQuote;
use Illuminate\Support\Facades\Auth;

class QuoteService
{
    public const HISTORY_ACTION = 'handmade_quote_sent';

    public function quoteFor(Order $order): OrderQuote
    {
        return OrderQuote::query()->firstOrNew(['order_id' => $order->getKey()]);
    }

    public function customerGroup(Order $order): string
    {
        return $order->getOrderMetadata(CustomOrderService::META_CUSTOMER_GROUP)
            ?: CustomerGroupEnum::SHIP_QT;
    }

    /**
     * Pre-fill a quote that has not been priced yet.
     *
     * - Product cost: for catalogue orders the listed prices already stand (decision #3),
     *   so we sum the line items. Custom orders start at 0 for HF to price.
     * - Fulfil / packing: defaults from settings, and only for the full-fulfilment group;
     *   the self-shipping group pays neither (decision #5).
     * - Shipping: always 0 by default. HF types it in only when we buy the label (decision #5).
     */
    public function withDefaults(Order $order): OrderQuote
    {
        $quote = $this->quoteFor($order);

        if ($quote->isQuoted()) {
            return $quote;
        }

        $isFullFulfilment = $this->customerGroup($order) === CustomerGroupEnum::SHIP_QT;

        $quote->product_cost = (float) $order->products->sum(
            fn ($product) => (float) $product->price * (int) $product->qty
        );

        $quote->shipping_cost = 0;
        $quote->fulfill_fee = $isFullFulfilment ? $this->defaultFulfillFee() : 0;
        $quote->packing_fee = $isFullFulfilment ? $this->defaultPackingFee($order) : 0;

        if (! $quote->expected_delivery_date) {
            $expected = $order->getOrderMetadata(CustomOrderService::META_EXPECTED_DATE);
            $quote->expected_delivery_date = $expected ?: null;
        }

        return $quote;
    }

    /**
     * Write the per-line unit prices onto the order items and return the resulting
     * product cost. Staff price each handmade piece separately, so the order total is
     * always the sum of its lines rather than a typed-in lump sum.
     *
     * @param  array<int, array{id: int|string, price: float|string}>  $items
     */
    public function applyItemPrices(Order $order, array $items): float
    {
        $prices = [];

        foreach ($items as $item) {
            $prices[(int) $item['id']] = (float) $item['price'];
        }

        $productCost = 0.0;

        foreach ($order->products as $product) {
            if (! array_key_exists($product->getKey(), $prices)) {
                // Line not submitted (e.g. stale form) — keep whatever it already had.
                $productCost += (float) $product->price * (int) $product->qty;

                continue;
            }

            $price = $prices[$product->getKey()];

            $product->forceFill(['price' => $price])->saveQuietly();

            $productCost += $price * (int) $product->qty;
        }

        return round($productCost, 2);
    }

    /**
     * @param  array{product_cost: float, shipping_cost: float, fulfill_fee: float, packing_fee: float, expected_delivery_date?: string|null, note?: string|null}  $data
     */
    public function save(Order $order, array $data): OrderQuote
    {
        $quote = $this->quoteFor($order);

        // A quote that was already sent and is being changed is a re-quote; the customer
        // needs to see that, and we want it in the order history either way.
        $isRevision = $quote->isQuoted();
        $revisionNumber = $this->timesQuoted($order) + 1;

        $quote->fill([
            'order_id' => $order->getKey(),
            'product_cost' => (float) $data['product_cost'],
            'shipping_cost' => (float) $data['shipping_cost'],
            'fulfill_fee' => (float) $data['fulfill_fee'],
            'packing_fee' => (float) $data['packing_fee'],
            'deposit_percent' => (int) ($data['deposit_percent'] ?? 50),
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'note' => $data['note'] ?? null,
            'quoted_by' => Auth::id(),
            'quoted_at' => now(),
        ]);

        $quote->save();

        // Reflect the agreed price on the order itself so admin lists and invoices are honest.
        $order->forceFill([
            'sub_total' => $quote->product_cost,
            'shipping_amount' => $quote->shipping_cost,
            'amount' => $quote->total,
        ])->saveQuietly();

        OrderHistory::query()->create([
            'action' => self::HISTORY_ACTION,
            'description' => trans(
                'plugins/handmade-workflow::handmade-workflow.quote.' . ($isRevision ? 'history_revised' : 'history_sent'),
                ['total' => format_price($quote->total), 'number' => $revisionNumber]
            ),
            'order_id' => $order->getKey(),
            'user_id' => Auth::check() ? Auth::id() : 0,
            'extras' => json_encode([
                'revision' => $revisionNumber,
                'product_cost' => $quote->product_cost,
                'shipping_cost' => $quote->shipping_cost,
                'fulfill_fee' => $quote->fulfill_fee,
                'packing_fee' => $quote->packing_fee,
                'total' => $quote->total,
            ]),
        ]);

        return $quote->refresh();
    }

    /**
     * How many times a quote has been sent for this order (0 = never quoted).
     */
    public function timesQuoted(Order $order): int
    {
        return OrderHistory::query()
            ->where('order_id', $order->getKey())
            ->where('action', self::HISTORY_ACTION)
            ->count();
    }

    public function defaultFulfillFee(): float
    {
        return (float) setting('handmade_default_fulfill_fee', 0);
    }

    /**
     * Packing material scales with how many pieces are packed.
     */
    public function defaultPackingFee(Order $order): float
    {
        $unitFee = (float) setting('handmade_default_packing_fee_per_unit', 0);

        return round($unitFee * (int) $order->products->sum('qty'), 2);
    }
}
