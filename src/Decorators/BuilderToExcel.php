<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Exception;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BuilderToExcel
{
    function toExcel(SectionFactory $factory, ?string $sezione = null, ?string $title = null)
    {
        /**
         * @var IteratorElement $fields
         */
        $elements = $factory->getSectionFields();

        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");

        $built = $iterator->getFromBuilt($elements);

        $labels = $this->getExcelFields($built);


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

        $res = $this->toXlsxArray($built);

        $row_index = 2;
        foreach ($res as $res_value) {
            foreach ($labels as $value) {
                $worksheet->setCellValue($value["column"] . $row_index, $res_value[$value["label"]] ?? null);
            }
            $row_index++;
        }

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
     * @return array<int, array<string, mixed>> a list of rows, one entry per section instance
     */
    function toXlsxArray(IteratorElement $data): array
    {
        if ($data->type === IteratorElement::SECTION) {
            return $this->parseXlsxArraySection($data);
        }

        return [[$data->csv_ref => $data->data]];
    }

    /**
     * A nested section always represents a repeatable ("root") relation, so each of its
     * instances becomes its own row. A section's direct fields are instead constant values,
     * replicated onto every row produced by its child sections (if any).
     *
     * @return array<int, array<string, mixed>>
     */
    function parseXlsxArraySection(IteratorElement $data): array
    {
        $base = [];
        $child_row_groups = [];

        foreach ($data->fields as $field) {
            if ($field->type === IteratorElement::SECTION) {
                $child_row_groups[] = $this->parseXlsxArraySection($field);
            } else {
                $base[$field->csv_ref] = isset($base[$field->csv_ref])
                    ? $base[$field->csv_ref] . "; " . $field->data
                    : $field->data;
            }
        }

        if (empty($child_row_groups)) {
            return [$base];
        }

        $rows = [];
        foreach ($child_row_groups as $group) {
            foreach ($group as $child_row) {
                $rows[] = array_merge($base, $child_row);
            }
        }

        return $rows;
    }

    function getExcelFields(IteratorElement &$fields)
    {
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
