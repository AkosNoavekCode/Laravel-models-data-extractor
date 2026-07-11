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

        $this->toCsvArray($iterator, $f, $labels);

        fclose($f);

        return $file_path;
    }

    /**
     * @param array<int, string> $labels column order, matching the header row
     */
    function toCsvArray(BuilderIterator &$builder, &$f, array $labels): void
    {
        $builder->buildUsing(function (?array $row = []) use ($f, $labels) {
            if ($row) {
                $line = [];
                foreach ($labels as $label) {
                    $line[] = $row[$label] ?? null;
                }
                fputcsv($f, $line);
            }
        }, false);
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
