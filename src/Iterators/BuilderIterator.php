<?php

namespace AkosNoavek\DataExtractor\Iterators;

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
                $val = $target?->{$part};
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
        $sections_to_push = [];
        $section->fields = array_values($section->fields);

        foreach ($section->fields as $section_index => &$value) {
            $this->parsedSections[] = $value;
            if ($value->type === IteratorElement::SECTION) {

                if (!empty($value->root)) {
                    $root_elements = $this->getValueFromPath($value, $value->root);
                    $reflection = null;
                    if ($root_elements) {
                        $reflection = new ReflectionClass($root_elements);
                    }

                    if (
                        $reflection
                        &&
                        (
                            // todo: check this
                            // $reflection->getParentClass()->name === SMIModel::class
                            $reflection->getParentClass()->name === Model::class
                        )
                    ) {
                        $this->current_target = $root_elements;
                        $this->parseSection($value);
                        $this->current_target = $this->target;
                    } else if (!empty($root_elements)) {
                        $pushable_index = 1;
                        foreach ($root_elements as $i => $target_element_model) {
                            $this->current_target = $target_element_model;
                            $this->parseSection($value);
                            if ($i !== count($root_elements) - 1) {
                                $sections_to_push[] = [
                                    'pushable' =>  new IteratorElement(json_decode(json_encode($value), true)),
                                    'target' => $this->getParent($value) ?? $value,
                                    'index' => $section_index + $pushable_index
                                ];
                                $pushable_index++;
                            }
                        }
                        $this->current_target = $this->target;
                    } else {
                        $this->clearFromEmptyFields($value);
                    }
                } else {
                    $this->parseSection($value);
                }

                if (! $value->evaluate_when_empty) {
                    $should_display = false;
                    foreach ($value->fields as $field) {
                        if (!empty($field->data)) {
                            $should_display = true;
                            break;
                        }
                    }

                    if (!$should_display) {
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

        foreach ($sections_to_push as $pushable) {
            $this->walk($pushable['target'], $pushable['index'], $pushable['pushable']);
        }
    }

    function getParent(IteratorElement $element): ?IteratorElement
    {
        if (empty($element->parent_key)) {
            return null;
        } else {
            return collect($this->parsedSections)->where('element_key', $element->parent_key)->first();
        }
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

    function walk(&$section, $index, $pushable)
    {
        if (!isset($section->fields[$index]))
            $section->fields[$index] = $pushable;

        $original = $section->fields[$index];
        if ($original) {
            $section->fields[$index] = $pushable;

            $nested_original = $section->fields[$index + 1] ?? null;

            if ($nested_original) {
                $this->walk($section, ($index + 1), $nested_original);
            }
            $section->fields[$index + 1] = $original;
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

                                if (!empty($records)) {
                                    $records->each(function ($record) use (&$models, $index) {
                                        $models[$index][] = $record;
                                    });
                                }
                                // $models[$index] = array_merge($models[$index], getPartValue(explode('.', $path), $model));
                            } else {
                                $records = $model->{$path};

                                if (!empty($records)) {
                                    $records->each(function ($record) use (&$models, $index) {
                                        $models[$index][] = $record;
                                    });
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
                        $row_value = $val?->{$el[count($el) - 1]};

                    if ($element->date) {
                        try {
                            $v = (!empty($row_value)) ? Carbon::parse($row_value)->format("d/m/Y") : "";
                        } catch (Exception $e) {
                            $v = "";
                        }
                    } else {
                        $v = $row_value;
                    }

                    if ($v && trim($v)) {
                        $str .= $end . trim($v);
                    }
                }
            }

            $val = $str;
        } else {
            $parts = explode('.', $key);

            $val = $this->getPartValue($parts, $this->current_target);

            if ($element->date) {
                try {
                    $val = (!empty($val)) ? Carbon::parse($val)->format("d/m/Y") : "";
                } catch (\Exception $e) {
                    try {
                        $val = (!empty($val)) ? Carbon::createFromFormat('d/m/Y', $val)->format("d/m/Y") : "";
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
