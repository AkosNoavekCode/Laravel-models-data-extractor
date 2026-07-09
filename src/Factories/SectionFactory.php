<?php

namespace AkosNoavek\DataExtractor\Factories;

use AkosNoavek\DataExtractor\Iterators\IteratorElement;

interface SectionFactory
{
    public static function make(string $file_path, ?string $section_name = null): static;

    public function getFieldPath(string $field): string;

    /**
     * Retrieves the fields for the section given in the constructor of the factory
     * else should return all sections
     */
    public function getSectionFields(): IteratorElement;
}
