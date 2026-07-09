<?php

namespace AkosNoavek\DataExtractor\Iterators;

use Exception;
use Illuminate\Support\Str;

class IteratorElement
{
    public string $label = "";

    public ?string $view = null;

    public string $element_key;

    public ?string $parent_key = null;

    public ?array $extra_attributes = null;

    public mixed $data = null;

    public ?string $path = null;

    /**
     * field|section
     */
    public ?string $type = null {
        get {
            return ($this->type ?? null) ? strtolower($this->type) : null;
        }
        set(?string $value) {
            $this->type = strtolower($value);
        }
    }

    public bool $date = false;

    public bool $evaluate_when_empty = true;

    public ?array $fields = null;

    /**
     * The root element of a section
     */
    public ?string $root = null;

    public const FIELD = 'field';

    public const SECTION = 'section';

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            $this->$key = $value;
        }

        $this->element_key = Str::random(6);

        if (empty($this->type)) {
            $this->type = self::FIELD;
        }

        throw_if(
            (
                empty($this->fields)
                && strtolower($this->type) === self::SECTION
            ),
            new Exception("Definizione campo non valida")
        );

        throw_if(
            (
                empty($this->path)
                && strtolower($this->type) === self::FIELD
            ),
            new Exception("Definizione campo non valida, si prega di aggiungere un path")
        );

        if ($this->fields)
            $this->parseElements($this->fields);
    }

    protected function parseElements(array &$fields)
    {
        if (
            !empty($fields)
        ) {
            foreach ($fields as $key => &$field) {
                if (
                    ! empty($key)
                    && empty(safe_value($field, 'label'))
                )
                    $field['label'] = $key;

                $field['parent_key'] = $this->element_key;
                $el = new IteratorElement($field);

                $field = $el;
            }
        }
    }
}
