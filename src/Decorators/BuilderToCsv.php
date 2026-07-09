<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;

trait BuilderToCsv
{
    use BuilderToArray;

    function toCsv(SectionFactory $factory, ?string $sezione = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");

        if ($sezione) {
            return $this->sanitizeExtracted($iterator->get());
        } else {
            return $this->sanitizeExtracted($iterator->get());
        }
    }

    function getFields(SectionFactory &$factory)
    {
        /**
         * @var IteratorElement $fields
         */
        $fields = $factory->getSectionFields();

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

    function getSectionFields(IteratorElement $fields, array &$labels, array &$pushed_labels)
    {
        foreach ($fields->fields as &$value) {
            if ($value->type === IteratorElement::SECTION)
                $this->getSectionFields($value, $labels, $pushed_labels);
            else {
                if (! in_array($value->label, $labels)) {
                    $def_value = $value->label;
                    $labels[] = $value->label;
                } else {
                    if (! isset($pushed_labels[$value->label]))
                        $pushed_labels[$value->label] = 2;
                    else
                        $pushed_labels[$value->label] = $pushed_labels[$value->label] + 1;

                    $def_value = "(" . $pushed_labels[$value->label] . ") " . $value->label;
                    $labels[] = $def_value;
                }
                $value->csv_ref = $def_value;
            }
        }
    }
}
