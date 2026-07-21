<?php

namespace Botble\HandmadeWorkflow\Services;

use Botble\HandmadeWorkflow\Http\Requests\CreateCustomOrderRequest;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Turns an uploaded .xlsx/.csv into rows the order form can be filled with.
 *
 * Nothing is saved and nothing is downloaded: photo cells are kept as the links
 * the customer wrote, and the order stores those links. The customer reviews the
 * parsed rows in the form and submits them.
 */
class CustomOrderImporter
{
    public const MAX_ROWS = CreateCustomOrderRequest::MAX_ITEMS;

    public const MAX_IMAGES_PER_ROW = CreateCustomOrderRequest::MAX_IMAGES_PER_ITEM;

    /** Guards against a 50k-row sheet being walked in full before we give up. */
    protected const SCAN_LIMIT = 500;

    /**
     * @return array{items: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        if ($rows === []) {
            $this->fail(trans('plugins/handmade-workflow::handmade-workflow.import.errors.empty_file'));
        }

        [$map, $rows] = $this->splitAtHeaderRow($rows);

        if ($missing = CustomOrderImportSchema::missingRequiredLabels($map)) {
            $this->fail(trans('plugins/handmade-workflow::handmade-workflow.import.errors.missing_columns', [
                'columns' => implode(', ', $missing),
            ]));
        }

        $rows = $this->withoutBlankRows($rows);

        if ($rows === []) {
            $this->fail(trans('plugins/handmade-workflow::handmade-workflow.import.errors.no_rows'));
        }

        $warnings = [];

        if (count($rows) > self::MAX_ROWS) {
            $warnings[] = trans('plugins/handmade-workflow::handmade-workflow.import.errors.too_many_rows', [
                'max' => self::MAX_ROWS,
                'total' => count($rows),
            ]);

            $rows = array_slice($rows, 0, self::MAX_ROWS, true);
        }

        // Pick one reading of d/m vs m/d for the whole file rather than guessing per
        // cell; only a date that lands in the future is reconsidered afterwards.
        $dateFormat = $this->resolveDateFormat($rows, $map);

        $items = [];

        foreach ($rows as $number => $row) {
            $item = $this->buildItem($row, $map, $dateFormat, $number);

            if ($item === null) {
                $warnings[] = trans('plugins/handmade-workflow::handmade-workflow.import.errors.row_skipped', [
                    'row' => $number,
                ]);

                continue;
            }

            $items[] = $item;
        }

        if ($items === []) {
            $this->fail(trans('plugins/handmade-workflow::handmade-workflow.import.errors.no_rows'));
        }

        foreach ($items as $item) {
            if (! $item['image_links']) {
                $warnings[] = trans('plugins/handmade-workflow::handmade-workflow.import.errors.no_image', [
                    'row' => $item['row'],
                    'name' => Str::limit($item['name'], 40),
                ]);
            }
        }

        return ['items' => $items, 'warnings' => $warnings];
    }

    /**
     * The header is the first row naming at least one column we know — sheets often
     * carry a title or a blank line above the real table.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{0: array<string, int>, 1: array<int, array<int, mixed>>}
     */
    protected function splitAtHeaderRow(array $rows): array
    {
        $position = 0;

        foreach ($rows as $row) {
            $map = CustomOrderImportSchema::mapHeaders($row);

            if ($map !== []) {
                return [$map, array_slice($rows, $position + 1, preserve_keys: true)];
            }

            $position++;
        }

        $this->fail(trans('plugins/handmade-workflow::handmade-workflow.import.errors.no_header'));
    }

    /**
     * @return array<int, array<int, mixed>> Sheet rows keyed by their real row number.
     */
    protected function readRows(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: 'csv');
        $path = $file->getRealPath();

        $reader = SimpleExcelReader::create($path, $extension === 'txt' ? 'csv' : $extension)
            ->noHeaderRow();

        if (in_array($extension, ['csv', 'txt'], true)) {
            $reader->useDelimiter($this->detectDelimiter($path));
        }

        $rows = [];
        $number = 1;

        foreach ($reader->getRows() as $row) {
            $rows[$number] = array_values((array) $row);

            if ($number++ >= self::SCAN_LIMIT) {
                break;
            }
        }

        $reader->close();

        return $rows;
    }

    /**
     * Excel in a Vietnamese locale often writes CSV with semicolons.
     */
    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return ',';
        }

        $line = (string) fgets($handle, 8192);
        fclose($handle);

        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<int, mixed>>
     */
    protected function withoutBlankRows(array $rows): array
    {
        return array_filter($rows, function (array $row): bool {
            foreach ($row as $value) {
                if ($this->text($value) !== '') {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array<string, mixed>|null Null when the row has no usable product.
     */
    protected function buildItem(array $row, array $map, string $dateFormat, int $number): ?array
    {
        $name = $this->cell($row, $map, 'name');

        if ($name === '') {
            return null;
        }

        [$recipientName, $recipientAddress] = $this->splitShippingInfo($this->cell($row, $map, 'shipping_info'));

        return [
            'row' => $number,
            'name' => Str::limit($name, 250, ''),
            'qty' => $this->qty($this->cell($row, $map, 'qty')),
            'note' => Str::limit($this->cell($row, $map, 'note'), 1000, '') ?: null,
            'sku' => Str::limit($this->cell($row, $map, 'sku'), 100, '') ?: null,
            'marketplace_order_id' => Str::limit($this->cell($row, $map, 'marketplace_order_id'), 100, '') ?: null,
            'ordered_at' => $this->date($this->raw($row, $map, 'ordered_at'), $dateFormat),
            'image_links' => $this->links($row, $map, 'image_urls'),
            'fabric_image_links' => $this->links($row, $map, 'fabric_image_urls'),
            'recipient_name' => Str::limit($recipientName, 150, '') ?: null,
            'recipient_address' => Str::limit($recipientAddress, 500, '') ?: null,
            'recipient_email' => $this->email($this->cell($row, $map, 'email')),
        ];
    }

    /**
     * One photo cell can hold several links separated by new lines, commas or spaces.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array<int, string>
     */
    protected function links(array $row, array $map, string $key): array
    {
        preg_match_all('#https?://\S+#i', $this->cell($row, $map, $key), $matches);

        $links = array_map(
            // Trailing punctuation is common when a link is pasted inside a sentence.
            static fn (string $url): string => rtrim($url, ".,;:)]}'\""),
            $matches[0]
        );

        return array_slice(array_values(array_unique(array_filter($links))), 0, self::MAX_IMAGES_PER_ROW);
    }

    /**
     * "Annaliesa Harriss\n19 Pinecone Street\nSunnybank QLD 4109\nAustralia"
     * → the first line is the person, everything after it is where it goes.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitShippingInfo(string $value): array
    {
        $lines = array_values(array_filter(
            array_map(trim(...), preg_split('/\R/', $value) ?: []),
            static fn (string $line): bool => $line !== ''
        ));

        if ($lines === []) {
            return ['', ''];
        }

        return [array_shift($lines), implode("\n", $lines)];
    }

    /**
     * Decide between d/m/Y and m/d/Y for the whole sheet. A day above 12 anywhere
     * settles it; otherwise we assume the Vietnamese reading.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, int>  $map
     */
    protected function resolveDateFormat(array $rows, array $map): string
    {
        foreach ($rows as $row) {
            $value = $this->cell($row, $map, 'ordered_at');

            if (! preg_match('#^(\d{1,2})[/-](\d{1,2})[/-]\d{2,4}$#', $value, $matches)) {
                continue;
            }

            if ((int) $matches[2] > 12) {
                return 'm/d/Y';
            }

            if ((int) $matches[1] > 12) {
                return 'd/m/Y';
            }
        }

        return 'd/m/Y';
    }

    protected function date(mixed $value, string $format): ?string
    {
        // .xlsx date cells arrive already typed; only text needs interpreting.
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = $this->text($value);

        if ($value === '') {
            return null;
        }

        foreach ([$format, 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'] as $candidate) {
            $date = $this->parseDate($value, $candidate);

            if ($date) {
                return $this->correctFutureDate($date, $value, $format)->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Strict on purpose: without checking the warnings, "7/13/2026" read as d/m/Y
     * rolls over into January 2027 instead of being rejected.
     */
    protected function parseDate(string $value, string $format): ?DateTimeInterface
    {
        $date = date_create_from_format('!' . $format, $value);
        $errors = date_get_last_errors();

        if (! $date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
            return null;
        }

        return $date;
    }

    /**
     * Sheets mix the two readings of 10/7 — some cells typed by hand, some formatted
     * by Excel. The order was already placed on the marketplace, so a date in the
     * future means we read day and month the wrong way round; swap just that cell.
     */
    protected function correctFutureDate(DateTimeInterface $date, string $value, string $format): DateTimeInterface
    {
        if ($date <= now()) {
            return $date;
        }

        $flipped = $this->parseDate($value, $format === 'm/d/Y' ? 'd/m/Y' : 'm/d/Y');

        return $flipped && $flipped <= now() ? $flipped : $date;
    }

    protected function qty(string $value): int
    {
        $qty = (int) preg_replace('/[^0-9]/', '', $value);

        return max($qty, 1);
    }

    protected function email(string $value): ?string
    {
        $value = Str::lower(trim($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     */
    protected function cell(array $row, array $map, string $key): string
    {
        return $this->text($this->raw($row, $map, $key));
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     */
    protected function raw(array $row, array $map, string $key): mixed
    {
        $index = $map[$key] ?? null;

        return $index === null ? null : ($row[$index] ?? null);
    }

    protected function text(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_scalar($value)) {
            return '';
        }

        // Stray quotes survive when a customer pastes a quoted block into a cell.
        return trim((string) $value, " \t\n\r\0\x0B\"");
    }

    protected function fail(string $message): never
    {
        throw new CustomOrderImportException($message);
    }
}
