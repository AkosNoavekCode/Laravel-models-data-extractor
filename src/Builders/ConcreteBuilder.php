<?php

namespace AkosNoavek\DataExtractor\Builders;

use AkosNoavek\DataExtractor\Decorators\BuilderToHtml;
use AkosNoavek\DataExtractor\Decorators\BuilderToJson;
use Exception;
use Illuminate\Support\Str;

class ConcreteBuilder extends DataExtractorBuilder
{
    use BuilderToHtml, BuilderToJson;

    public array $sezioni;
    public array $pushed_sections = [];

    public function __construct(
        protected mixed $target,
        protected ?string $filename = null,
        protected ?string $content = null
    ) {
        if (!empty($content)) {
            $rand = Str::random(12);
            $this->filename = "/tmp/$rand.json";
            file_put_contents($this->filename, "test 123");
        } elseif (empty($content) && empty($this->filename)) {
            throw new Exception("Please provide a valid schema");
        }
        $this->sezioni = array_keys(json_decode(file_get_contents($this->filename), true));
    }
}
