<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Database\Eloquent\Model;

/**
 * Schema condiviso dai test: una section esterna con un field diretto
 * ("field_one") e una section annidata ("inner_section") con due field
 * propri ("field_two", "field_three").
 */
function nestedSectionSchema(): array
{
    return [
        "type" => "section",
        "label" => "outer section label",
        "fields" => [
            "field_one" => ["path" => "field_one", "label" => "label one"],
            "inner_section" => [
                "type" => "section",
                "label" => "inner section label",
                "fields" => [
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                    "field_three" => ["path" => "field_three", "label" => "label three"],
                ],
            ],
        ],
    ];
}

describe('Model implementation is working', function () {
    test('HTML method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: nestedSectionSchema());

        expect($el)->toContain($concrete->field_one);
        expect($el)->toContain($concrete->field_two);
        expect($el)->toContain($concrete->field_three);
        expect($el)->toContain('label one');
        expect($el)->toContain('label two');
        expect($el)->toContain('label three');
    });

    test('Array method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: nestedSectionSchema());

        expect($el[0]['data'][0]['value'])->toBe($concrete->field_one);
        expect($el[0]['data'][1]['data'][0]['value'])->toBe($concrete->field_two);
        expect($el[0]['data'][1]['data'][1]['value'])->toBe($concrete->field_three);
    });

    test('Json method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: nestedSectionSchema()));

        expect($el[0]->data[0]->value)->toBe($concrete->field_one);
        expect($el[0]->data[1]->data[0]->value)->toBe($concrete->field_two);
        expect($el[0]->data[1]->data[1]->value)->toBe($concrete->field_three);
    });

    test('Extract method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: nestedSectionSchema());

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete->field_one);
        expect($el->fields[1]->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[1]->fields[0]->data)->toBe($concrete->field_two);
        expect($el->fields[1]->fields[1]->data)->toBe($concrete->field_three);
    });
});

describe('Array implementation is working', function () {
    test('HTML method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: nestedSectionSchema());

        expect($el)->toContain($concrete['field_one']);
        expect($el)->toContain($concrete['field_two']);
        expect($el)->toContain($concrete['field_three']);
        expect($el)->toContain('label one');
        expect($el)->toContain('label two');
        expect($el)->toContain('label three');
    });

    test('Array method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: nestedSectionSchema());

        expect($el[0]['data'][0]['value'])->toBe($concrete['field_one']);
        expect($el[0]['data'][1]['data'][0]['value'])->toBe($concrete['field_two']);
        expect($el[0]['data'][1]['data'][1]['value'])->toBe($concrete['field_three']);
    });

    test('Json method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ];

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: nestedSectionSchema()));

        expect($el[0]->data[0]->value)->toBe($concrete['field_one']);
        expect($el[0]->data[1]->data[0]->value)->toBe($concrete['field_two']);
        expect($el[0]->data[1]->data[1]->value)->toBe($concrete['field_three']);
    });

    test('Extract method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ];

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: nestedSectionSchema());

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete['field_one']);
        expect($el->fields[1]->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[1]->fields[0]->data)->toBe($concrete['field_two']);
        expect($el->fields[1]->fields[1]->data)->toBe($concrete['field_three']);
    });
});

describe('Object implementation is working', function () {
    test('HTML method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: nestedSectionSchema());

        expect($el)->toContain($concrete->field_one);
        expect($el)->toContain($concrete->field_two);
        expect($el)->toContain($concrete->field_three);
        expect($el)->toContain('label one');
        expect($el)->toContain('label two');
        expect($el)->toContain('label three');
    });

    test('Array method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: nestedSectionSchema());

        expect($el[0]['data'][0]['value'])->toBe($concrete->field_one);
        expect($el[0]['data'][1]['data'][0]['value'])->toBe($concrete->field_two);
        expect($el[0]['data'][1]['data'][1]['value'])->toBe($concrete->field_three);
    });

    test('Json method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ]));

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: nestedSectionSchema()));

        expect($el[0]->data[0]->value)->toBe($concrete->field_one);
        expect($el[0]->data[1]->data[0]->value)->toBe($concrete->field_two);
        expect($el[0]->data[1]->data[1]->value)->toBe($concrete->field_three);
    });

    test('Extract method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
            "field_three" => "value three",
        ]));

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: nestedSectionSchema());

        expect($el->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->data)->toBe($concrete->field_one);
        expect($el->fields[1]->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[1]->fields[0]->data)->toBe($concrete->field_two);
        expect($el->fields[1]->fields[1]->data)->toBe($concrete->field_three);
    });
});
