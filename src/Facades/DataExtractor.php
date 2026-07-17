<?php

namespace AkosNoavek\DataExtractor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed make(mixed $target)
 * @method static mixed extract(?string $filename = null, mixed $data = null, ?string $section = null, bool $should_delete_template = true)
 * @method static mixed toCsv(?string $filename = null, mixed $data = null, ?string $section = null)
 * @method static mixed toJson(?string $filename = null, mixed $data = null, ?string $section = null)
 * @method static mixed toHtml(?string $filename = null, mixed $data = null, ?string $section = null)
 * @method static mixed toXlsx(?string $filename = null, mixed $data = null, ?string $section = null, ?callable $using = null)
 * @method static mixed toArray(?string $filename = null, mixed $data = null, ?string $section = null)
 *
 * @see \AkosNoavek\DataExtractor\Services\DataExtractor
 */
class DataExtractor extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'data_extractor';
  }
}
