<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Exception;
use Illuminate\Support\Str;

trait BuilderToCsv
{
    function toCsv(SectionFactory $factory, ?string $sezione = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");

        $labels = $this->getFields($factory);

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

    /**
     * @return array<int, array<string, mixed>> a list of rows, one entry per section instance
     */
    function toCsvArray(IteratorElement $data): array
    {
        if ($data->type === IteratorElement::SECTION) {
            return $this->parseCsvArraySection($data);
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
    function parseCsvArraySection(IteratorElement $data): array
    {
        $base = [];
        $child_row_groups = [];

        foreach ($data->fields as $field) {
            if ($field->type === IteratorElement::SECTION) {
                $child_row_groups[] = $this->parseCsvArraySection($field);
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

    function getFields(SectionFactory &$factory)
    {
        /**
         * @var IteratorElement $fields
         */
        $fields = $factory->getSectionFields();

        if ($fields->type === IteratorElement::FIELD) {
            $labels[] = $fields->label;
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
            if ($value->type === IteratorElement::SECTION) {
                throw_unless(!empty($value->root), new Exception(
                    "Invalid CSV/Excel schema: nested section \"{$value->label}\" has no 'root'. "
                        . "A non-repeating nested section only ever produces a single set of values, "
                        . "which is useless in a flat export — declare its fields directly on the parent section instead."
                ));

                $this->getSectionFields($value, $labels, $pushed_labels);
            } else {
                if (! in_array($value->label, $labels)) {
                    $labels[] = $value->label;
                }
            }
        }
    }
}
