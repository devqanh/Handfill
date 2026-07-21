<?php

namespace Botble\HandmadeWorkflow\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds the blank .xlsx customers download, fill in and upload back.
 *
 * Sheet 1 carries nothing but the header row on purpose — sample rows there would
 * come back as real products on import. The worked example lives on sheet 2.
 */
class CustomOrderTemplateWriter
{
    protected const HEADER_BACKGROUND = 'FF1F3864';

    protected const REQUIRED_BACKGROUND = 'FFC00000';

    /**
     * @return string Absolute path of the generated file; the caller deletes it after sending.
     */
    public function store(): string
    {
        $spreadsheet = new Spreadsheet();

        $this->buildOrderSheet($spreadsheet->getActiveSheet());
        $this->buildGuideSheet($spreadsheet->createSheet());

        $spreadsheet->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'handmade-template-') . '.xlsx';

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function fileName(): string
    {
        return 'mau-don-hang-handfill.xlsx';
    }

    protected function buildOrderSheet(Worksheet $sheet): void
    {
        $sheet->setTitle(CustomOrderImportSchema::SHEET_NAME);

        $columns = CustomOrderImportSchema::columns();
        $index = 1;

        foreach ($columns as $key => $definition) {
            $letter = Coordinate::stringFromColumnIndex($index++);

            $sheet->setCellValue("{$letter}1", $definition['label']);
            $sheet->getColumnDimension($letter)->setWidth($definition['width']);

            // Required columns get their own colour so a customer scanning the sheet
            // can tell at a glance what must not be left blank.
            $sheet->getStyle("{$letter}1")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB($definition['required'] ? self::REQUIRED_BACKGROUND : self::HEADER_BACKGROUND);

            // The full explanation rides along as a cell comment: hovering a header
            // beats making people switch to the guide sheet.
            $sheet->getComment("{$letter}1")
                ->getText()
                ->createTextRun(CustomOrderImportSchema::description($key));
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->freezePane('A2');

        $this->prepareDataRows($sheet, array_keys($columns), $lastColumn);
    }

    /**
     * Pre-format the empty rows so pasted multi-line addresses keep their line breaks
     * and long numeric IDs are not rewritten as 4.11279E+09 by Excel.
     *
     * @param  array<int, string>  $keys
     */
    protected function prepareDataRows(Worksheet $sheet, array $keys, string $lastColumn): void
    {
        $lastRow = CustomOrderImporter::MAX_ROWS + 1;

        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        foreach (['marketplace_order_id', 'sku', 'ordered_at'] as $key) {
            $letter = $this->letterOf($keys, $key);

            $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        $qty = $this->letterOf($keys, 'qty');

        $validation = new DataValidation();
        $validation
            ->setType(DataValidation::TYPE_WHOLE)
            ->setOperator(DataValidation::OPERATOR_GREATERTHAN)
            ->setFormula1(0)
            ->setAllowBlank(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle(CustomOrderImportSchema::columns()['qty']['label'])
            ->setError(trans('plugins/handmade-workflow::handmade-workflow.import.qty_rule'));

        // Keyed by range, which is what the writer emits as the validation's sqref.
        $sheet->setDataValidation("{$qty}2:{$qty}{$lastRow}", $validation);
    }

    protected function buildGuideSheet(Worksheet $sheet): void
    {
        $sheet->setTitle(CustomOrderImportSchema::GUIDE_SHEET_NAME);

        $sheet->fromArray([
            trans('plugins/handmade-workflow::handmade-workflow.import.guide_column'),
            trans('plugins/handmade-workflow::handmade-workflow.import.guide_required'),
            trans('plugins/handmade-workflow::handmade-workflow.import.guide_description'),
            trans('plugins/handmade-workflow::handmade-workflow.import.guide_example'),
        ], null, 'A1');

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BACKGROUND]],
        ]);

        $yes = trans('plugins/handmade-workflow::handmade-workflow.import.required_yes');
        $no = trans('plugins/handmade-workflow::handmade-workflow.import.required_no');

        $row = 2;

        foreach (CustomOrderImportSchema::columns() as $key => $definition) {
            $sheet->fromArray([
                $definition['label'],
                $definition['required'] ? $yes : $no,
                CustomOrderImportSchema::description($key),
                $definition['example'],
            ], null, "A$row");

            $sheet->getStyle("A$row")->getFont()->setBold($definition['required']);

            $row++;
        }

        $lastRow = $row - 1;

        $sheet->getStyle("A2:D{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        foreach (['A' => 26, 'B' => 12, 'C' => 68, 'D' => 40] as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }

        $footer = $lastRow + 2;

        $sheet->setCellValue("A$footer", trans('plugins/handmade-workflow::handmade-workflow.import.guide_footer', [
            'max' => CustomOrderImporter::MAX_ROWS,
        ]));
        $sheet->mergeCells("A$footer:D$footer");
        $sheet->getStyle("A$footer")->getFont()->setItalic(true);
        $sheet->getStyle("A$footer")->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($footer)->setRowHeight(58);
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function letterOf(array $keys, string $key): string
    {
        return Coordinate::stringFromColumnIndex(array_search($key, $keys, true) + 1);
    }
}
