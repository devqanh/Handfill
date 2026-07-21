<?php

namespace Botble\HandmadeWorkflow\Http\Requests;

use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
use Botble\HandmadeWorkflow\Services\ImageLinkChecker;
use Botble\Support\Http\Requests\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateCustomOrderRequest extends Request
{
    public const MAX_ITEMS = 50;

    public const MAX_IMAGES_PER_ITEM = 6;

    /** The two photo fields the customer fills in as free text, one link per line. */
    public const LINK_FIELDS = ['image_links', 'fabric_image_links'];

    /**
     * The link boxes are textareas, so they arrive as one string per item. Split them
     * here and every array rule below applies unchanged.
     */
    protected function prepareForValidation(): void
    {
        $items = (array) $this->input('items', []);

        foreach ($items as $index => $item) {
            foreach (self::LINK_FIELDS as $field) {
                $value = Arr::get($item, $field);

                if (is_string($value)) {
                    $items[$index][$field] = self::splitLinks($value);
                }
            }
        }

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<int, string>
     */
    public static function splitLinks(string $value): array
    {
        $lines = preg_split('/[\r\n,\s]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(trim(...), $lines))));
    }

    public function rules(): array
    {
        return [
            'customer_group' => ['required', 'string', Rule::in(CustomerGroupEnum::values())],
            // Only needed when the rows do not carry their own recipient — an imported
            // marketplace sheet ships every line to a different buyer.
            'address_id' => [
                Rule::requiredIf(fn (): bool => ! $this->everyItemHasRecipient()),
                'nullable',
                Rule::exists('ec_customer_addresses', 'id')
                    ->where('customer_id', Auth::guard('customer')->id()),
            ],
            'expected_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1', 'max:' . self::MAX_ITEMS],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.marketplace_order_id' => ['nullable', 'string', 'max:100'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.ordered_at' => ['nullable', 'date'],

            // Photos picked in the browser…
            'items.*.images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES_PER_ITEM],
            'items.*.images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            // …or referenced by link, which is what an imported sheet carries. Links are
            // stored as written; we never copy the file onto our own storage.
            'items.*.image_links' => ['nullable', 'array', 'max:' . self::MAX_IMAGES_PER_ITEM],
            'items.*.image_links.*' => ['string', 'url', 'max:2048'],
            'items.*.fabric_image_links' => ['nullable', 'array', 'max:' . self::MAX_IMAGES_PER_ITEM],
            'items.*.fabric_image_links.*' => ['string', 'url', 'max:2048'],

            'items.*.recipient_name' => ['nullable', 'string', 'max:150'],
            'items.*.recipient_address' => ['nullable', 'string', 'max:500'],
            'items.*.recipient_email' => ['nullable', 'email', 'max:191'],
        ];
    }

    public function after(): array
    {
        return [
            $this->everyItemHasAPhoto(...),
            $this->everyLinkOpens(...),
        ];
    }

    /**
     * A line has to show us *something* to work from: either an uploaded file or a
     * link we can open.
     */
    protected function everyItemHasAPhoto(Validator $validator): void
    {
        foreach ((array) $this->input('items', []) as $index => $item) {
            if ($this->file("items.$index.images") || Arr::get($item, 'image_links')) {
                continue;
            }

            $validator->errors()->add(
                "items.$index.images",
                trans('plugins/handmade-workflow::handmade-workflow.item_images_required', [
                    'index' => $index + 1,
                ])
            );
        }
    }

    /**
     * Since we keep the customer's links instead of copying the files, a dead link
     * means the order carries nothing to make the item from — so it is refused here
     * rather than discovered weeks later at production.
     *
     * Every link on the order is checked in one pass: they are probed concurrently
     * and the result is cached, so this costs seconds even for a full sheet.
     */
    protected function everyLinkOpens(Validator $validator): void
    {
        $items = (array) $this->input('items', []);
        $links = [];

        foreach ($items as $item) {
            foreach (self::LINK_FIELDS as $field) {
                $links = array_merge($links, Arr::wrap(Arr::get($item, $field, [])));
            }
        }

        if (! $links) {
            return;
        }

        $statuses = app(ImageLinkChecker::class)->check($links);

        foreach ($items as $index => $item) {
            foreach (self::LINK_FIELDS as $field) {
                foreach (Arr::wrap(Arr::get($item, $field, [])) as $link) {
                    if (($statuses[$link] ?? ImageLinkChecker::BROKEN) !== ImageLinkChecker::BROKEN) {
                        continue;
                    }

                    $validator->errors()->add(
                        "items.$index.$field",
                        trans('plugins/handmade-workflow::handmade-workflow.item_link_broken', [
                            'index' => $index + 1,
                            'link' => Str::limit($link, 70),
                        ])
                    );
                }
            }
        }
    }

    protected function everyItemHasRecipient(): bool
    {
        $items = (array) $this->input('items', []);

        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (! trim((string) Arr::get($item, 'recipient_name')) || ! trim((string) Arr::get($item, 'recipient_address'))) {
                return false;
            }
        }

        return true;
    }

    public function attributes(): array
    {
        $trans = fn (string $key): string => trans("plugins/handmade-workflow::handmade-workflow.$key");

        return [
            'customer_group' => $trans('customer_group'),
            'address_id' => $trans('shipping_address'),
            'expected_date' => $trans('expected_date'),
            'items' => $trans('items'),
            'items.*.name' => $trans('item_name'),
            'items.*.qty' => $trans('item_qty'),
            'items.*.images' => $trans('item_images'),
            'items.*.sku' => $trans('item_sku'),
            'items.*.ordered_at' => $trans('item_ordered_at'),
            'items.*.marketplace_order_id' => $trans('item_marketplace_order_id'),
            'items.*.recipient_name' => $trans('item_recipient_name'),
            'items.*.recipient_email' => $trans('item_recipient_email'),
            'items.*.recipient_address' => $trans('item_recipient_address'),
        ];
    }
}
