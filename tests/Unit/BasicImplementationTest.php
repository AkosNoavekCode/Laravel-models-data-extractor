<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Database\Eloquent\Model;

describe('Model implementation is working', function () {
    test('CSV method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field = "value test";


        /**
         * @var string $file_path
         */
        $file_path = DataExtractor::make($concrete)
            ->toCsv(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one_evaluated" => ["path" => "field", "label" => $label_one = "label one"],
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_three" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_four" => [
                        "type" => "section",
                        "label" => "section label",
                        "fields" => [
                            "field_three" => ["path" => "field_two", "label" => $label_two = "label three"],
                            "field_four" => ["path" => "field_two", "label" => $label_two = "label two"],
                        ]
                    ],
                ],
            ]);

        $content = file_get_contents($file_path);
        expect($content)->toContain($concrete->field);
    });

    test('HTML method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field = "value test";


        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "path" => "field",
                "label" => $label = "test label"
            ]);

        expect($el)->toContain($concrete->field);
        expect($el)->toContain($label);
    });

    test('Array method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field = "value test";


        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el['value'])->toBe($concrete->field);
    });

    test('Json method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field = "value test";


        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "path" => "field",
                "label" => "test label"
            ]));

        expect($el->value)->toBe($concrete->field);
    });

    test('Extract method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field = "value test";


        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el->data)->toBe($concrete->field);
    });
});

describe('Array implementation is working', function () {
    test('CSV method is working as expected', function () {
        $concrete = [
            "field" => "value test",
            "label" => "label test"
        ];

        /**
         * @var string $file_path
         */
        $file_path = DataExtractor::make($concrete)
            ->toCsv(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one_evaluated" => ["path" => "field", "label" => $label_one = "label one"],
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_three" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_four" => [
                        "type" => "section",
                        "label" => "section label",
                        "fields" => [
                            "field_three" => ["path" => "field_two", "label" => $label_two = "label three"],
                            "field_four" => ["path" => "field_two", "label" => $label_two = "label two"],
                        ]
                    ],
                ],
            ]);

        $content = file_get_contents($file_path);
        expect($content)->toContain($concrete['field']);
    });

    test('HTML method is working as expected', function () {
        $concrete = [
            "field" => "value test"
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "path" => "field",
                "label" => $label = "test label"
            ]);

        expect($el)->toContain($concrete['field']);
        expect($el)->toContain($label);
    });

    test('Array method is working as expected', function () {
        $concrete = [
            "field" => "value test"
        ];

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el['value'])->toBe($concrete["field"]);
    });

    test('Json method is working as expected', function () {
        $concrete = [
            'field' => "value test"
        ];

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "path" => "field",
                "label" => "test label"
            ]));

        expect($el->value)->toBe($concrete['field']);
    });

    test('Extract method is working as expected', function () {
        $concrete = ['field' => "value test"];

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el->data)->toBe($concrete['field']);
    });
});

describe('Object implementation is working', function () {
    test('CSV method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field" => "value test",
            "label" => "label test"
        ]));

        /**
         * @var string $file_path
         */
        $file_path = DataExtractor::make($concrete)
            ->toCsv(data: [
                "type" => "section",
                "label" => "section label",
                "fields" => [
                    "field_one_evaluated" => ["path" => "field", "label" => $label_one = "label one"],
                    "field_one" => ["path" => "field_one", "label" => $label_one = "label one"],
                    "field_two" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_three" => ["path" => "field_two", "label" => $label_two = "label two"],
                    "field_four" => [
                        "type" => "section",
                        "label" => "section label",
                        "fields" => [
                            "field_three" => ["path" => "field_two", "label" => $label_two = "label three"],
                            "field_four" => ["path" => "field_two", "label" => $label_two = "label two"],
                        ]
                    ],
                ],
            ]);

        $content = file_get_contents($file_path);
        expect($content)->toContain($concrete->field);
    });

    test('HTML method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field" => "value test"
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toHtml(data: [
                "path" => "field",
                "label" => $label = "test label"
            ]);

        expect($el)->toContain($concrete->field);
        expect($el)->toContain($label);
    });

    test('Array method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field" => "value test"
        ]));

        /**
         * @var object $el
         */
        $el = DataExtractor::make($concrete)
            ->toArray(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el['value'])->toBe($concrete->field);
    });

    test('Json method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field" => "value test"
        ]));

        /**
         * @var object $el
         */
        $el = json_decode(DataExtractor::make($concrete)
            ->toJson(data: [
                "path" => "field",
                "label" => "test label"
            ]));

        expect($el->value)->toBe($concrete->field);
    });

    test('Extract method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field" => "value test"
        ]));

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($concrete)
            ->extract(data: [
                "path" => "field",
                "label" => "test label"
            ]);

        expect($el->data)->toBe($concrete->field);
    });
});
