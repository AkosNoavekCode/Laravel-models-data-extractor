<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;

trait BuilderToJson
{
    function toJson(SectionFactory $factory, ?string $sezione = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: "<br>");
        if ($sezione) {
            return json_encode($this->sanitizeExtractedValues($iterator->get()));
        } else {
            //todo
            // return json_encode($this->sanitizeExtractedValues($iterator->get()));
        }
    }

    function sanitizeExtractedValues(IteratorElement $data): array
    {
        $res = [];
        if ($data->type === IteratorElement::SECTION) {
            $res[] = $this->parseSection($data);
        } else {
            $res = [
                "label" => $data->label,
                "value" => $data->data,
                "view" => $data->view,
                "element_key" => $data->element_key,
                "parent_key" => $data->parent_key,
                "extra_attributes" => $data->extra_attributes,
            ];
        }

        return $res;
    }

    function parseSection(IteratorElement $data)
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
                ];
            else
                $section['data'][] = $this->parseSection($field);
        }
        return $section;
    }
}
