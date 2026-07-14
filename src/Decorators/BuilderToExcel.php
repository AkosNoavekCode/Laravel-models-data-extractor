<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\SectionFactory;
use AkosNoavek\DataExtractor\Iterators\BuilderIterator;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Exception;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

trait BuilderToExcel
{
    function toExcel(SectionFactory $factory, ?string $sezione = null, ?string $title = null, ?callable $using = null)
    {
        $iterator = new BuilderIterator(target: $this->target, factory: $factory, separator: ";");

        $labels = $this->getExcelFields($factory);

        $writer = new Writer();
        $writer->openToFile($file_path = "/tmp/" . Str::random(6) . ".xlsx");

        if ($title)
            $writer->getCurrentSheet()->setName($title);

        $writer->addRow(Row::fromValues($labels));

        $this->toXlsxArray($iterator, $writer, $labels, $using);

        $writer->close();

        return $file_path;
    }

    /**
     * @param array<int, string> $labels column order, matching the header row
     */
    function toXlsxArray(BuilderIterator &$iterator, Writer $writer, array $labels, ?callable $using = null): void
    {
        $iterator->buildUsing(function (?array $row = []) use ($writer, $labels, $using) {
            if ($row) {
                $line = [];
                foreach ($labels as $label) {
                    // Cast to string so cells are written as plain text, matching the
                    // CSV export and avoiding Excel auto-coercing numeric/date-looking
                    // values (e.g. codes with leading zeros) into numbers or dates.
                    $value = $row[$label] ?? null;
                    $line[] = is_null($value) ? null : (string) $value;
                }
                $writer->addRow(Row::fromValues($line));

                if ($using)
                    $using($row);
            }
        }, false);
    }

    function getExcelFields($factory)
    {
        /**
         * @var IteratorElement $fields
         */
        $fields = $factory->getSectionFields();

        if ($fields->type === IteratorElement::FIELD) {
            $labels[] = $fields->label;
            $fields->csv_ref = $fields->label;
        } else {
            $labels = [];
            $this->getExcelSectionFields($fields, $labels);
        }

        return $labels;
    }

    function getExcelSectionFields(IteratorElement &$fields, array &$labels)
    {
        foreach ($fields->fields as &$value) {
            if ($value->type === IteratorElement::SECTION) {
                throw_unless(!empty($value->root), new Exception(
                    "Invalid CSV/Excel schema: nested section \"{$value->label}\" has no 'root'. "
                        . "A non-repeating nested section only ever produces a single set of values, "
                        . "which is useless in a flat export — declare its fields directly on the parent section instead."
                ));

                $this->getExcelSectionFields($value, $labels);
            } else {
                if (! in_array($value->label, $labels)) {
                    $labels[] = $value->label;
                }
                $value->csv_ref = $value->label;
            }
        }
    }
}
