<?php

namespace AkosNoavek\DataExtractor\Decorators\Iterators;

use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

trait AdvancedIteratorBuilder
{
    function buildUsing(callable $callback, bool $standard = true)
    {
        $el = $this->getParts();

        if (strtolower($el->type) === IteratorElement::SECTION) {
            if ($standard) {
                $this->parseSectionUsing($el, $callback, $standard);
            } else {
                foreach ($this->collectRows($el) as $row) {
                    $callback($row);
                }
            }
        } else {
            $callback([$el->label => $this->getValueFromPath($el)]);
        }
    }

    /**
     * @param IteratorElement $section
     * @param callable $callback
     * Used algorithm to replace the data assignment
     * @param bool $standard
     * If standard before calling the algorithm the parsed data
     * will be assigned to an IteratorElement
     */
    function parseSectionUsing(IteratorElement &$section, callable $callback, bool $standard = true)
    {
        $section->fields = array_values($section->fields);

        foreach ($section->fields as $section_index => &$value) {
            $this->parsedSections[] = $value;
            if ($value->type === IteratorElement::SECTION) {

                if (!empty($value->root)) {
                    // If Data intensive one can use the laravel cursor wich supports the
                    // foreach statement while paginating the records correcly
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
                        // Case in wich the root element of a section is a model
                        // In this case we know we have a single target
                        $this->current_target = $root_elements;
                        $this->parseSectionUsing($value, $callback, $standard);
                        $this->current_target = $this->target;
                    } else if (!empty($root_elements)) {

                        /**
                         * Case in wich we have multiple root elements
                         * This is triggered with array and plain object
                         * Care on how you define a section root
                         */
                        foreach ($root_elements as $target_element_model) {
                            $this->current_target = $target_element_model;

                            foreach ($value->fields as &$leaf_element) {
                                $val = $this->parseElement($leaf_element);
                                $leaf_element->data = $val;
                            }

                            $callback($value);
                        }

                        $this->current_target = $this->target;
                    }
                } else {
                    $this->parseSectionUsing($value, $callback, $standard);
                }
            } else {
                $val = $this->parseElement($value);
                $value->data = $val;
                if (! $value->evaluate_when_empty && empty($value->data)) {
                    unset($section->fields[$section_index]);
                } else {
                    $callback($value);
                }
            }
        }
    }

    /**
     * Builds the flat rows a CSV/Excel export needs out of the section tree.
     *
     * A section's own (non-section) fields are constant values, replicated
     * onto every row produced by its nested "root" sections. A nested section
     * always represents a repeatable relation — whether it resolves to a
     * single related model or a collection — so each of its instances
     * contributes its own row(s), merged with the constants of its parent(s).
     *
     * @return array<int, array<string, mixed>> a list of rows, values keyed by
     *  label — the caller looks them up against the labels collected by
     *  getFields()/getExcelFields()
     */
    function collectRows(IteratorElement &$section): array
    {
        $base = [];
        $child_row_groups = [];

        foreach ($section->fields as &$value) {
            $this->parsedSections[] = $value;

            if ($value->type === IteratorElement::SECTION) {
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
                    // Root resolves to a single related model: still exactly
                    // one nested row (group), whatever fields it contains.
                    $this->current_target = $root_elements;
                    $child_row_groups[] = $this->collectRows($value);
                    $this->current_target = $this->target;
                } elseif (!empty($root_elements)) {
                    // Root resolves to a collection: one nested row per item.
                    $rows = [];
                    foreach ($root_elements as $target_element_model) {
                        $this->current_target = $target_element_model;
                        $rows = array_merge($rows, $this->collectRows($value));
                    }
                    $this->current_target = $this->target;
                    $child_row_groups[] = $rows;
                } else {
                    $child_row_groups[] = [];
                }
            } else {
                $val = $this->parseElement($value);
                $base[$value->label] = isset($base[$value->label])
                    ? $base[$value->label] . "; " . $val
                    : $val;
            }
        }

        if (empty($child_row_groups)) {
            return [$base];
        }

        $rows = [];
        foreach ($child_row_groups as $group) {
            foreach ($group as $child_row) {
                $rows[] = array_merge($base, $child_row);
            }
        }

        return $rows;
    }
}
