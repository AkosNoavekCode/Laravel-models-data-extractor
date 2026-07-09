<?php

namespace AkosNoavek\DataExtractor\Facades;

use Illuminate\Support\Facades\Facade;

class DataExtractor extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'data_extractor';
  }
}
