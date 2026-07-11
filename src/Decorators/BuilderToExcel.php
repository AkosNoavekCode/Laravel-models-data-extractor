<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Exception;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BuilderToExcel
{
    function toExcel(SectionFactory $factory, ?string $sezione = null, ?string $title = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");

        $labels = $this->getExcelFields($factory);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getSheet(0);
        if ($title)
            $worksheet->setTitle($title);

        $header_index = 0;

        foreach ($labels as $value) {
            $worksheet->setCellValue($value["column"] . 1, $value["label"]);

            $style = $worksheet->getStyle($this->getColumnLetter($header_index) . ":" .  $this->getColumnLetter($header_index));
            $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

            $header_index++;
        }

        $this->toXlsxArray($iterator, $worksheet, $labels);

        $writer = new Xlsx($spreadsheet);
        $writer->save($file_path = "/tmp/" . Str::random(6) . ".xlsx");

        return $file_path;
    }

    private function getColumnLetter($index): string
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65) . $letters;
            $index = floor($index / 26) - 1;
        }

        return $letters;
    }

    /**
     * @param array<int, array{label: string, column: string}> $labels
     */
    function toXlsxArray(BuilderIterator &$iterator, Worksheet &$worksheet, array $labels): void
    {
        $index = 2;
        $iterator->buildUsing(function (?array $row = []) use (&$worksheet, &$index, $labels) {
            if ($row) {
                foreach ($labels as $value) {
                    $worksheet->setCellValue($value["column"] . $index, $row[$value["label"]] ?? null);
                }
                $index++;
            }
        }, false);
    }

    function getExcelFields($factory)
    {
        /**
         * @var IteratorElement $fields
         */
        $fields = $factory->getSectionFields();

        if ($fields->type === IteratorElement::FIELD) {
            $labels[] = ["label" => $fields->label, "column" => $this->getColumnLetter(0)];
            $fields->csv_ref = $fields->label;
        } else {
            $labels = [];
            $i = 0;
            $this->getExcelSectionFields($fields, $labels, $i);
        }

        return $labels;
    }

    function getExcelSectionFields(IteratorElement &$fields, array &$labels, int &$i)
    {
        foreach ($fields->fields as &$value) {
            if ($value->type === IteratorElement::SECTION) {
                throw_unless(!empty($value->root), new Exception(
                    "Invalid CSV/Excel schema: nested section \"{$value->label}\" has no 'root'. "
                        . "A non-repeating nested section only ever produces a single set of values, "
                        . "which is useless in a flat export — declare its fields directly on the parent section instead."
                ));

                $this->getExcelSectionFields($value, $labels, $i);
            } else {
                if (! in_array($value->label, array_column($labels, 'label'))) {
                    $labels[] = ["label" => $value->label, "column" => $this->getColumnLetter($i)];
                    $i++;
                }
                $value->csv_ref = $value->label;
            }
        }
    }
}
