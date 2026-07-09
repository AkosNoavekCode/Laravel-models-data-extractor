<?php

namespace AkosNoavek\DataExtractor\Iterators;

use AkosNoavek\DataExtractor\Iterators\IteratorElement;

interface BuilderIteratorInterface
{
    function previous(): mixed;

    function next(): mixed;

    function get(): mixed;

    static function getPartValue(array $parts, mixed $target): mixed;

    function getParts(): IteratorElement;
}
