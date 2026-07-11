<?php

namespace AkosNoavek\DataExtractor\Iterators;

use AkosNoavek\DataExtractor\Decorators\Iterators\AdvancedIteratorBuilder;
use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIteratorInterface;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Override;
use ReflectionClass;

/**
 * @param mixed $target
 * @param SectionFactory $factory
 */
class BuilderIterator implements BuilderIteratorInterface
{
    use AdvancedIteratorBuilder;

    public mixed $current_target;

    public string $curr_index;

    private array $parsedSections = [];

    public function __construct(
        private mixed $target,
        private SectionFactory $factory,
        private ?string $separator = ";",
    ) {
        $this->current_target = $target;
    }

    #[Override]
    function previous(): mixed
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    function next(): mixed
    {
        throw new \Exception('Not implemented');
    }

    static function getPartValue(array $parts, mixed $target): mixed
    {
        $val = null;

        if (is_array($target)) {
            return safe_value($target, implode(".", $parts));
        }

        foreach ($parts as $i => $part) {
            if ($i == 0)
                $val = $target?->{$part} ?? null;
            elseif ($i != 0 && $val == null)
                break;
            else
                $val = $val?->{$part} ?? null;
        }

        return $val;
    }

    function getParts(): IteratorElement
    {
        return $this->factory->getSectionFields();
    }

    function getFromBuilt(IteratorElement $el)
    {
        if (strtolower($el->type) === IteratorElement::SECTION) {
            $this->parseSection($el);
        } else {
            $el->data = $this->getValueFromPath($el);
        }

        return $el;
    }


    function get(): mixed
    {
        $el = $this->getParts();

        if (strtolower($el->type) === IteratorElement::SECTION) {
            $this->parseSection($el);
        } else {
            $el->data = $this->getValueFromPath($el);
        }

        return $el;
    }

    function parseSection(IteratorElement &$section)
    {
        $replacements = [];
        $section->fields = array_values($section->fields);

        foreach ($section->fields as $section_index => &$value) {
            $this->parsedSections[] = $value;
            if ($value->type === IteratorElement::SECTION) {

                if (!empty($value->root)) {
                    $root_elements = $this->getValueFromPath($value, $value->root);
                    $reflection = null;
                    if (is_object($root_elements)) {
                        $reflection = new ReflectionClass($root_elements);
                    }

                    if (
                        $reflection
                        &&
                        (
                            $reflection->getParentClass()
                            && (
                                in_array($reflection->getParentClass()->name, config('data_extractor.model_classes'))
                                || $reflection->getParentClass()->name === Model::class
                            )
                        )
                    ) {
                        $this->current_target = $root_elements;
                        $this->parseSection($value);
                        $this->current_target = $this->target;
                    } else if (!empty($root_elements)) {
                        $clones = [];
                        foreach ($root_elements as $target_element_model) {
                            $this->current_target = $target_element_model;
                            $clone = new IteratorElement(json_decode(json_encode($value), true));
                            $this->parseSection($clone);
                            if ($this->sectionShouldDisplay($clone)) {
                                $clones[] = $clone;
                            }
                        }
                        $this->current_target = $this->target;
                        $replacements[$section_index] = $clones;
                    } else {
                        $this->clearFromEmptyFields($value);
                    }
                } else {
                    $this->parseSection($value);
                }

                if (! $value->evaluate_when_empty && !isset($replacements[$section_index])) {
                    if (!$this->sectionShouldDisplay($value)) {
                        unset($section->fields[$section_index]);
                    }
                }
            } else {
                $val = $this->parseElement($value);
                $value->data = $val;
                if (! $value->evaluate_when_empty && empty($value->data)) {
                    unset($section->fields[$section_index]);
                }
            }
        }

        foreach (array_reverse($replacements, true) as $index => $clones) {
            array_splice($section->fields, $index, 1, $clones);
        }
        $section->fields = array_values($section->fields);
    }

    function sectionShouldDisplay(IteratorElement $section): bool
    {
        foreach ($section->fields as $field) {
            if (!empty($field->data)) {
                return true;
            }
        }
        return false;
    }

    function clearFromEmptyFields(IteratorElement &$target)
    {
        if ($target->type === IteratorElement::SECTION) {
            foreach ($target->fields as $index => $value) {
                if ($value->type === IteratorElement::SECTION)
                    $this->clearFromEmptyFields($value);

                if (!$value->evaluate_when_empty)
                    unset($target->fields[$index]);
            }
        } else {
            if (
                ! $target->evaluate_when_empty
                && empty($value->value)
            )
                unset($target);
        }
    }

    function parseElement(IteratorElement $el): mixed
    {
        return $this->getValueFromPath($el);
    }

    function getValueFromPath(IteratorElement $element, ?string $key_override = null): mixed
    {
        $key = $key_override ?? $element->path;

        /**
         * Multiple elements parsing
         */
        if (str_contains($key, ".*.")) {
            $el = explode(".*.", $key);
            $str = "";

            if (count($el) > 2) {
                $model = null;
                $rows = [];
                $models = [];

                foreach ($el as $index => $path) {
                    $models[$index] = [];
                    if ($index === 0)
                        $models[$index - 1][] = $this->current_target;
                    elseif ($index !== 0 && count($models) === 0)
                        break;

                    foreach ($models[$index - 1] as $model) {

                        if ($index === count($el) - 1) {
                            break;
                        } else {
                            if (str_contains($path, '.')) {
                                $records = $this->getPartValue(explode('.', $path), $model);

                                /**
                                 * We check if an empty record should be pushed
                                 * Since the !empty() method covers only for arrays and objects
                                 * not collection we need to add a reflection check on the method count
                                 */
                                $should_push = false;
                                try {
                                    $reflection = new ReflectionClass($records);
                                    if (
                                        $reflection->hasMethod('count')
                                        && $records->count() === 0
                                    ) {
                                        $should_push = true;
                                    }
                                } catch (\Exception $e) {
                                }
                                if (!empty($records)) {
                                    $records->each(function ($record) use (&$models, $index) {
                                        $models[$index][] = $record;
                                    });

                                    if ($should_push) {
                                        $models[$index][] = [];
                                    }
                                } else {
                                    $models[$index][] = [];
                                }
                                // $models[$index] = array_merge($models[$index], getPartValue(explode('.', $path), $model));
                            } elseif ($model) {
                                $records = $model->{$path};

                                /**
                                 * We check if an empty record should be pushed
                                 * Since the !empty() method covers only for arrays and objects
                                 * not collection we need to add a reflection check on the method count
                                 */
                                $should_push = false;
                                try {
                                    $reflection = new ReflectionClass($records);
                                    if (
                                        $reflection->hasMethod('count')
                                        && $records->count() === 0
                                    ) {
                                        $should_push = true;
                                    }
                                } catch (\Exception $e) {
                                }

                                if (!empty($records)) {
                                    $records->each(function ($record) use (&$models, $index) {
                                        $models[$index][] = $record;
                                    });

                                    if ($should_push) {
                                        $models[$index][] = [];
                                    }
                                } else {
                                    $models[$index][] = [];
                                }
                            }
                        }
                        unset($models[-1]);
                        $rows = array_last($models);
                    }
                }
            } else {
                if (str_contains($el[0], '.'))
                    $rows = $this->getPartValue(explode('.', $el[0]), $this->current_target);
                else
                    $rows = $this->current_target->{$el[0]};
            }

            if (!empty($rows)) {
                foreach ($rows as $i => $val) {
                    $end = ($i != 0) ? $this->separator : "";

                    if (str_contains($el[count($el) - 1], '.'))
                        $row_value = $this->getPartValue(explode('.', $el[count($el) - 1]), $val);
                    else
                        $row_value = safe_value($val, $el[count($el) - 1]);

                    if ($element->date) {
                        try {
                            $v = (!empty($row_value)) ? Carbon::parse($row_value)->format(config('data_extractor.date_format')) : "";
                        } catch (Exception $e) {
                            $v = "";
                        }
                    } else {
                        $v = $row_value;
                    }

                    if ($v && trim($v)) {
                        $str .= trim($v) . $end;
                    } else if (str_contains($key, ".*.")) {
                        $str .= $this->separator;
                    }
                }
            }

            $val = $str;
        } else {
            $parts = explode('.', $key);

            $val = $this->getPartValue($parts, $this->current_target);

            if ($element->date) {
                try {
                    $val = (!empty($val)) ? Carbon::parse($val)->format(config('data_extractor.date_format')) : "";
                } catch (\Exception $e) {
                    try {
                        $val = (!empty($val)) ? Carbon::createFromFormat('d/m/Y', $val)->format(config('data_extractor.date_format')) : "";
                    } catch (\Exception $e) {
                        $val = "";
                    }
                }
            } else {
                $val = $val;
            }
        }

        return $val;
    }
}
