<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
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

        $data = [];
        foreach ($labels as  $value) {
            $data[] = ["column" => $value["column"], "value" => $res[$value["label"]] ?? null];
            $worksheet->setCellValue($value["column"] . 2, $res[$value["label"]] ?? null);
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

    function toXlsxArray(IteratorElement $data)
    {
        $res = [];

        if ($data->type === IteratorElement::SECTION) {
            $res = $this->parseXlsxArraySection($data);
        } else {
            $res[$data->csv_ref] = $data->data;
        }

        return $res;
    }

    function parseXlsxArraySection(IteratorElement $data, array &$res = []): array
    {
        foreach ($data->fields as $field) {

            if ($field->type !== IteratorElement::SECTION) {
                if (!isset($res[$field->csv_ref])) {
                    $res[$field->csv_ref] = null;
                    $separator = "";
                } else {
                    $separator = "; ";
                }

                $res[$field->csv_ref] .= ($separator . $field->data);
            } else {
                $this->parseXlsxArraySection($field, $res);
            }
        }

        return $res;
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
