<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;

trait BuilderToArray
{
    function toArray(SectionFactory $factory, ?string $sezione = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");
        if ($sezione) {
            return $this->sanitizeExtracted($iterator->get());
        } else {
            return $this->sanitizeExtracted($iterator->get());
        }
    }

    function sanitizeExtracted(IteratorElement $data): array
    {
        $res = [];
        if ($data->type === IteratorElement::SECTION) {
            $res[] = $this->parseArraySection($data);
        } else {
            $res = [
                "label" => $data->label,
                "value" => $data->data,
                "view" => $data->view,
                "element_key" => $data->element_key,
                "parent_key" => $data->parent_key,
                "extra_attributes" => $data->extra_attributes,
                "csv_ref" => $data->csv_ref
            ];
        }

        return $res;
    }

    function parseArraySection(IteratorElement $data)
    {
        $section = [
            "label" => $data->label,
            "view" => $data->view,
            "extra_attributes" => $data->extra_attributes,
            "element_key" => $data->element_key,
            "parent_key" => $data->parent_key,
        ];

        foreach ($data->fields as $field) {
            if ($field->type !== IteratorElement::SECTION)
                $section['data'][] = [
                    "label" => $field->label,
                    "value" => $field->data,
                    "view" => $field->view,
                    "extra_attributes" => $field->extra_attributes,
                    "element_key" => $field->element_key,
                    "parent_key" => $field->parent_key,
                    "csv_ref" => $field->csv_ref
                ];
            else
                $section['data'][] = $this->parseArraySection($field);
        }
        return $section;
    }
}
