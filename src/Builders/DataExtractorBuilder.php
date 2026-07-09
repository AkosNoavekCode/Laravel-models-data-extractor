<?php

namespace AkosNoavek\DataExtractor\Builders;

abstract class DataExtractorBuilder
{
    public function __construct(
        protected mixed $target,
    ) {}

    function toXml() {}
}
