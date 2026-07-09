<?php

namespace AkosNoavek\DataExtractor\Decorators;

use AkosNoavek\DataExtractor\Factories\Models\ModelSectionFieldsFactory;
use Illuminate\Support\Facades\Blade;

trait BuilderToHtml
{
    function toHtml(?string $sezione = null)
    {
        if ($sezione) {
            $factory = ModelSectionFieldsFactory::make($this->filename, $sezione);
            $data = json_decode($this->toJson(sezione: $sezione, factory: $factory), true);
            return $this->arrayToHtml($data);
        } else {
            $factory = ModelSectionFieldsFactory::make($this->filename);
            $data = json_decode($this->toJson(factory: $factory), true);
            /**
             * If is not a section
             */
            if (!empty(safe_value($data, 'value'))) {
                return $this->itemToHtml($data, false);
            } else {
                return $this->arrayToHtml($data);
            }
        }
    }

    function arrayToHtml(array $data)
    {
        $str = "";

        foreach ($data as $value) {
            if (! empty(safe_value($value, 'data'))) {
                $str .= $this->parseSectionElement($value);
            } else {
                $str .= $this->itemToHtml($value, false);
            }
        }

        return Blade::render($str);
    }

    function parseSectionElement(array $value): string
    {
        $str = "";

        if (! safe_value($value, 'view'))
            $str .= "<ul>";

        foreach ($value['data'] as $item) {
            $str .= $this->itemToHtml($item, true);
        }

        if (! safe_value($value, 'view'))
            $str .= "</ul>";

        $str .= $this->itemToHtml($value, false);

        return $str;
    }

    function itemToHtml(array $value, bool $check_nested = false)
    {
        if (!empty(safe_value($value, 'data')) && $check_nested)
            return $this->arrayToHtml([$value]);


        if (safe_value($value, 'view')) {

            if (in_array($value['element_key'], $this->pushed_sections))
                return;

            $view = view(safe_value($value, 'view'), ['leaf' => $value, 'target' => $this->target, 'partKey' => $value['element_key'], 'parent_key' => $value['parent_key']]);
            $this->pushed_sections[] = $value['element_key'];

            return Blade::render($view->render());
        }

        if (
            (
                empty(safe_value($value, 'value'))
                && ! safe_value($value, 'evaluate_when_empty')
            )
        )
            return;

        $r = !empty(safe_value($value, 'value')) ? safe_value($value, 'value') : "-";

        if (
            str_contains($r, "<br>")
            && strpos($r, "<br>") > 1
        )
            $r = "<br>" . $r;

        return  <<<HTML
            <li>
                {$value['label']}: <strong>{$r}</strong>
            </li>
HTML;
    }
}
