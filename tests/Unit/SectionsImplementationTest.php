<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Database\Eloquent\Model;

describe('Model implementation is working', function () {
    test('HTML method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                ],
            ]);

        expect($el)->toContain($concrete->field_one);
        expect($el)->toContain($concrete->field_two);
        expect($el)->toContain($label_one);
        expect($el)->toContain($label_two);
    });

    test('Array method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el[0]['data'][0]['value'])->toBe($concrete->field_one);
        expect($el[0]['data'][1]['value'])->toBe($concrete->field_two);
    });

    test('Json method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]));

        expect($el[0]->data[0]->value)->toBe($concrete->field_one);
        expect($el[0]->data[1]->value)->toBe($concrete->field_two);
    });

    test('Extract method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete->field_one);
        expect($el->fields[1]->data)->toBe($concrete->field_two);
    });
});

describe('Array implementation is working', function () {
    test('HTML method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                ],
            ]);

        expect($el)->toContain($concrete['field_one']);
        expect($el)->toContain($concrete['field_two']);
        expect($el)->toContain($label_one);
        expect($el)->toContain($label_two);
    });

    test('Array method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el[0]['data'][0]['value'])->toBe($concrete['field_one']);
        expect($el[0]['data'][1]['value'])->toBe($concrete['field_two']);
    });

    test('Json method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
        ];

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]));

        expect($el[0]->data[0]->value)->toBe($concrete['field_one']);
        expect($el[0]->data[1]->value)->toBe($concrete['field_two']);
    });

    test('Extract method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
        ];

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete['field_one']);
        expect($el->fields[1]->data)->toBe($concrete['field_two']);
    });
});

describe('Object implementation is working', function () {
    test('HTML method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                ],
            ]);

        expect($el)->toContain($concrete->field_one);
        expect($el)->toContain($concrete->field_two);
        expect($el)->toContain($label_one);
        expect($el)->toContain($label_two);
    });

    test('Array method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el[0]['data'][0]['value'])->toBe($concrete->field_one);
        expect($el[0]['data'][1]['value'])->toBe($concrete->field_two);
    });

    test('Json method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
        ]));

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]));

        expect($el[0]->data[0]->value)->toBe($concrete->field_one);
        expect($el[0]->data[1]->value)->toBe($concrete->field_two);
    });

    test('Extract method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
        ]));

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete->field_one);
        expect($el->fields[1]->data)->toBe($concrete->field_two);
    });
});
