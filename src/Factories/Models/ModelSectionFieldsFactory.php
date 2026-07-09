<?php

namespace AkosNoavek\DataExtractor\Factories\Models;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Override;

class ModelSectionFieldsFactory implements SectionFactory
{
    private function __construct(protected readonly string $file_path, public ?string $section_name = null) {}

    public static function make(string $file_path, ?string $section_name = null): static
    {
        return new static(file_path: $file_path, section_name: $section_name);
    }

    /**
     * todo
     */
    public function getFieldPath(string $field): string
    {
        return '';
    }

    /**
     * Returns the fields for a given section
     * if it exists
     */
    #[Override]
    public function getSectionFields(): IteratorElement
    {
        $data = json_decode(file_get_contents($this->file_path), true);

        if (isset($data[$this->section_name]))
            $data = $data[$this->section_name];

        return new IteratorElement($data);
    }
}
