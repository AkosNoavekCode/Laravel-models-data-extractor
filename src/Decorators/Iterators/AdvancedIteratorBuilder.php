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
                $this->collectRows($el, $callback);
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
     * Each completed row is emitted through $callback as an array keyed by
     * label — the caller looks the values up against the labels collected by
     * getFields()/getExcelFields().
     *
     * @param array<string, mixed> $inherited constants from parent section(s),
     *  replicated onto every row this section produces
     */
    function collectRows(IteratorElement &$section, callable $callback, array $inherited = []): void
    {
        // Constants inherited from the parent section(s) are the starting point
        // for this section's rows: they must be replicated onto every row it and
        // its nested sections produce.
        $base = $inherited;

        // First collect this section's own constant fields, then process the
        // nested sections. Doing this in two passes means a constant field keeps
        // its column and value on every nested row regardless of its position
        // relative to the nested section in the schema.
        $nested = [];

        foreach ($section->fields as &$value) {
            if ($value->type === IteratorElement::SECTION) {
                $nested[] = &$value;
            } else {
                $val = $this->parseElement($value);
                $base[$value->label] = isset($base[$value->label])
                    ? $base[$value->label] . "; " . $val
                    : $val;
            }
        }
        unset($value);

        // A section without nested sections contributes exactly one flat row.
        if (empty($nested)) {
            $callback($base);
            return;
        }

        // Each nested section is a repeatable relation: it produces its own
        // row(s), each merged with the constants gathered above.
        foreach ($nested as &$value) {
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
                $this->collectRows($value, $callback, $base);
                $this->current_target = $this->target;
            } elseif (!empty($root_elements)) {
                // Root resolves to a collection: one nested row per item.
                if ($root_elements::class === "Illuminate\Database\Eloquent\Builder") {
                    $take = 1000;
                    $skip = 0;
                    $res = $root_elements->skip($skip)->take($take)->get();
                    while ($res->isNotEmpty()) {
                        if ($skip) {
                            $res = $root_elements->skip($skip)->take($take)->get();
                        }
                        foreach ($res as $target_element_model) {
                            $this->current_target = $target_element_model;
                            $this->collectRows($value, $callback, $base);
                        }
                        $skip += $take;
                    }
                } else {
                    foreach ($root_elements as $target_element_model) {
                        $this->current_target = $target_element_model;
                        $this->collectRows($value, $callback, $base);
                    }
                }

                $this->current_target = $this->target;
            }
        }
        unset($value);
    }
}
