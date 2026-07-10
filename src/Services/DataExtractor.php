<?php

namespace AkosNoavek\DataExtractor\Services;

use AkosNoavek\DataExtractor\Builders\ConcreteBuilder;
use AkosNoavek\DataExtractor\Factories\Models\ModelSectionFieldsFactory;
use Exception;

class DataExtractor
{
  protected mixed $target;

  private bool $extracted = false;

  protected ConcreteBuilder $builder;

  protected ModelSectionFieldsFactory $factory;

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

    $s = new self();
    $s->target = $this->target;

    return $s;
  }

  function extract(?string $filename = null, mixed $data = null, ?string $section = null, bool $should_delete_template = true)
  {
    throw_if(
      empty($filename)
        && empty($data),
      new Exception("Please provide a schema.")
    );

    $this->builder = new ConcreteBuilder($this->target, $filename, ($data) ? json_encode($data) : null);
    $this->factory = ModelSectionFieldsFactory::make($this->builder->filename, $section);
    $this->extracted = true;

    $data = $this->builder->extract($this->factory, $section);
    if ($this->builder->should_delete_template && $should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }

  function toCsv(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section, false);
    }

    $this->factory->section_name = $section;
    $data = $this->builder->toCsv($this->factory, $section);
    if ($this->builder->should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }

  function toJson(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section, false);
    }

    $this->factory->section_name = $section;
    $data = $this->builder->toJson($this->factory, $section);
    if ($this->builder->should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }

  function toHtml(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section, false);
    }

    $this->factory->section_name = $section;
    $data = $this->builder->toHtml();
    if ($this->builder->should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }

  function toXlsx(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section, false);
    }

    $this->factory->section_name = $section;
    $data = $this->builder->toExcel($this->factory);
    if ($this->builder->should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }

  function toArray(?string $filename = null, mixed $data = null, ?string $section = null)
  {
    if (! $this->extracted) {
      $this->extract($filename, $data, $section, false);
    }

    $this->factory->section_name = $section;
    $data = $this->builder->toArray($this->factory);
    if ($this->builder->should_delete_template)
      unlink($this->builder->filename);
    return $data;
  }
}
