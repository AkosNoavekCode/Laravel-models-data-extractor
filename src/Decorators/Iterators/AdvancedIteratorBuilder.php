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
            $this->parseSectionUsing($el, $callback, $standard);
        } else {
            $callback([$this->getValueFromPath($el)]);
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
                        if ($standard === true) {
                            foreach ($root_elements as $target_element_model) {
                                $this->current_target = $target_element_model;

                                foreach ($value->fields as &$leaf_element) {
                                    $val = $this->parseElement($leaf_element);
                                    $leaf_element->data = $val;
                                }

                                $callback($value);
                            }
                        } else {
                            foreach ($root_elements as $target_element_model) {
                                $this->current_target = $target_element_model;
                                $data = [];

                                foreach ($value->fields as &$leaf_element) {
                                    $val = $this->parseElement($leaf_element);
                                    $data[] = $val;
                                }

                                $callback($data);
                            }
                        }

                        $this->current_target = $this->target;
                    }
                } else {
                    $this->parseSectionUsing($value, $callback, $standard);
                }
            } else {
                $val = $this->parseElement($value);
                if ($standard === true) {
                    $value->data = $val;
                    if (! $value->evaluate_when_empty && empty($value->data)) {
                        unset($section->fields[$section_index]);
                    } else {
                        $callback($value);
                    }
                } else if (
                    /**
                     * Displayable
                     */
                    ! (! $value->evaluate_when_empty && empty($val))
                    /**
                     * Not standard
                     */
                    && ! $standard
                )
                    $callback([$val]);
            }
        }
    }
}
