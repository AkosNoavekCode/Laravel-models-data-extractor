<?php

namespace AkosNoavek\DataExtractor\Services;

use AkosNoavek\DataExtractor\Builders\ConcreteBuilder;
use AkosNoavek\DataExtractor\Factories\Models\ModelSectionFieldsFactory;
use Exception;

class DataExtractor
{
  protected mixed $target;

  private bool $extracted;

  protected ConcreteBuilder $builder;

  protected ModelSectionFieldsFactory $factory;

  public function test()
  {
    return "Works";
  }

  /**
   * @param mixed $target
   */
  function make(mixed $target)
  {
    throw_if(
      !is_array($target)
        && !is_object($target),
      new Exception("The provided target is not a valid target.")
    );

    $this->target = $target;

    return $this;
  }

  function extract(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    throw_if(
      empty($filename)
        && empty($data),
      new Exception("Please provide a schema.")
    );

    $this->builder = new ConcreteBuilder($this->target, $filename, json_encode($data));
    $this->factory = ModelSectionFieldsFactory::make($filename, $section);
    $this->extracted = true;
  }

  function toJson(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section);
    }

    $this->factory->section_name = $section;
    return $this->builder->toJson($this->factory);
  }
}
