<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Support\Str;

trait BuilderToCsv
{
    function toCsv(SectionFactory $factory, ?string $sezione = null)
    {
        /**
         * @var IteratorElement $fields
         */
        $elements = $factory->getSectionFields();

        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");

        $built = $iterator->getFromBuilt($elements);

        $labels = $this->getFields($built);

        $f = fopen($file_path = "/tmp/" . Str::random(6) . ".csv", 'wr');
        fputcsv($f, $labels, ",");

        $res = $this->toCsvArray($built);

        $data = [];

        foreach ($res as $res_value) {
            $parsed = [];
            foreach ($labels as $key => $value) {
                $parsed[$key] = $res_value[$value] ?? null;
            }
            $data[] = $parsed;
        }

        foreach ($data as $value) {
            fputcsv($f, $value, ",");
        }
        fclose($f);

        return $file_path;
    }

    function toCsvArray(IteratorElement $data)
    {
        $res = [];

        if ($data->type === IteratorElement::SECTION) {
            $res = $this->parseCsvArraySection($data);
        } else {
            $res[$data->csv_ref] = $data->data;
        }

        return $res;
    }

    function parseCsvArraySection(IteratorElement $data, array &$res = []): array
    {
        $csv_res = [];

        foreach ($data->fields as $field) {
            if (!isset($csv_res[$field->csv_ref])) {
                $separator = "";
            } else {
                $separator = "; ";
            }

            if ($field->type !== IteratorElement::SECTION) {
                if (! empty($data->root)) {
                    $csv_res[$field->csv_ref] = ($separator . $field->data);
                }
            } else
                $this->parseCsvArraySection($field, $res);
        }

        if ($csv_res)
            $res[] = $csv_res;

        return $res;
    }

    function getFields(IteratorElement &$fields)
    {
        if ($fields->type === IteratorElement::FIELD) {
            $labels[] = $fields->label;
            $fields->csv_ref = $fields->label;
        } else {
            $labels = [];
            $pushed_labels = [];
            $this->getSectionFields($fields, $labels, $pushed_labels);
        }

        return $labels;
    }

    function getSectionFields(IteratorElement &$fields, array &$labels, array &$pushed_labels)
    {
        foreach ($fields->fields as &$value) {
            if ($value->type === IteratorElement::SECTION)
                $this->getSectionFields($value, $labels, $pushed_labels);
            else {
                if (! in_array($value->label, $labels)) {
                    $labels[] = $value->label;
                }
                $value->csv_ref = $value->label;
            }
        }
    }
}
