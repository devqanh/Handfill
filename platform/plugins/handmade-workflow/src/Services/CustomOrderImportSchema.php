<?php

namespace Botble\HandmadeWorkflow\Services;

use Illuminate\Support\Str;

/**
 * Single source of truth for the order file customers exchange with us: the same
 * column list builds the downloadable .xlsx template and reads an uploaded file
 * back in. Change a column here and both ends follow.
 */
class CustomOrderImportSchema
{
    public const SHEET_NAME = 'Đơn hàng';

    public const GUIDE_SHEET_NAME = 'Hướng dẫn';

    /**
     * Header labels are deliberately NOT translated. They are a file format, not
     * UI: customers already send us sheets with exactly these Vietnamese headers
     * (see dev/MrChinh-Handfill-Order.csv), so a translated header would stop
     * their existing files from importing. Only the descriptions are localised.
     *
     * `aliases` are normalised header spellings we also accept, so a sheet that
     * says "Ma SKU" or "Quantity" still lands in the right column.
     *
     * @return array<string, array{label: string, aliases: array<int, string>, required: bool, width: int, example: string}>
     */
    public static function columns(): array
    {
        return [
            'marketplace_order_id' => [
                'label' => 'Order ID',
                'aliases' => ['madon', 'madonhang', 'madonsan', 'ordernumber', 'orderno'],
                'required' => false,
                'width' => 16,
                'example' => '4112788779',
            ],
            'sku' => [
                'label' => 'SKU',
                'aliases' => ['masku', 'masanpham', 'productcode'],
                'required' => false,
                'width' => 18,
                'example' => '250821-KC002',
            ],
            'ordered_at' => [
                'label' => 'Ngày order',
                'aliases' => ['ngaydat', 'ngaydathang', 'orderdate', 'date'],
                'required' => false,
                'width' => 14,
                'example' => '10/7/2026',
            ],
            'image_urls' => [
                'label' => 'Ảnh SP',
                'aliases' => ['anhsanpham', 'linkanh', 'linkanhsp', 'productimage', 'image'],
                'required' => false,
                'width' => 42,
                'example' => 'https://example.com/anh-mau.png',
            ],
            'name' => [
                'label' => 'Sản phẩm',
                'aliases' => ['tensanpham', 'tensp', 'productname', 'product'],
                'required' => true,
                'width' => 26,
                'example' => 'Party Crown',
            ],
            'note' => [
                'label' => 'Personalization / ghi chú',
                'aliases' => ['personalization', 'ghichu', 'note', 'yeucau', 'mota'],
                'required' => false,
                'width' => 46,
                'example' => "Personalization: 1. Pink 2. Pompoms màu tím và xanh dương\nStyle: Style 3\nSize: 50 cm",
            ],
            'fabric_image_urls' => [
                'label' => 'Ảnh màu vải (nếu có)',
                'aliases' => ['anhmauvai', 'anhvai', 'linkanhvai', 'fabricimage', 'colorimage'],
                'required' => false,
                'width' => 34,
                'example' => 'https://example.com/mau-vai.jpg',
            ],
            'shipping_info' => [
                'label' => 'INFO SHIPPING',
                'aliases' => ['thongtinnhanhang', 'diachinhanhang', 'diachi', 'shippingaddress', 'address'],
                'required' => false,
                'width' => 38,
                'example' => "Annaliesa Harriss\n19 Pinecone Street\nSunnybank QLD 4109\nAustralia",
            ],
            'email' => [
                'label' => 'Email',
                'aliases' => ['emailnguoinhan', 'mail', 'customeremail'],
                'required' => false,
                'width' => 26,
                'example' => 'buyer@example.com',
            ],
            'qty' => [
                'label' => 'Số lượng',
                'aliases' => ['sl', 'quantity', 'qty'],
                'required' => true,
                'width' => 10,
                'example' => '1',
            ],
        ];
    }

    public static function description(string $key): string
    {
        return trans("plugins/handmade-workflow::handmade-workflow.import.columns.$key");
    }

    /**
     * Strip case, accents and punctuation so "Ảnh màu vải (nếu có)", "anh mau vai"
     * and "ANH_MAU_VAI" all collapse to the same token.
     */
    public static function normalize(?string $header): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii((string) $header)));
    }

    /**
     * Locate each known column in a sheet's header row.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int> column key => zero-based position in the row
     */
    public static function mapHeaders(array $headerRow): array
    {
        $normalized = [];

        foreach (array_values($headerRow) as $index => $value) {
            $token = self::normalize(is_scalar($value) ? (string) $value : '');

            // First occurrence wins: a trailing duplicate column (common when a
            // sheet is copied) must not shadow the real one.
            if ($token !== '' && ! isset($normalized[$token])) {
                $normalized[$token] = $index;
            }
        }

        $map = [];

        foreach (self::columns() as $key => $column) {
            $candidates = array_merge([self::normalize($column['label'])], $column['aliases']);

            foreach ($candidates as $candidate) {
                if (isset($normalized[$candidate])) {
                    $map[$key] = $normalized[$candidate];

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Columns the sheet must carry for a row to mean anything.
     *
     * @param  array<string, int>  $map
     * @return array<int, string> human-readable labels of what is missing
     */
    public static function missingRequiredLabels(array $map): array
    {
        $missing = [];

        foreach (self::columns() as $key => $column) {
            if ($column['required'] && ! isset($map[$key])) {
                $missing[] = $column['label'];
            }
        }

        return $missing;
    }
}
