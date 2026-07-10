<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Legge la prima sheet di un file xlsx come matrice [riga][colonna],
 * cosi' come fa fgetcsv/str_getcsv per i test CSV.
 */
function readXlsxSheet(string $file_path): array
{
    return IOFactory::load($file_path)->getSheet(0)->toArray();
}

describe('Model implementation is working', function () {
    test('Excel method is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";

        $file_path = DataExtractor::make($concrete)
            ->toXlsx(data: [
                "type" => "section",
                "label" => "outer section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        [$header, $row] = readXlsxSheet($file_path);

        expect($header)->toBe(['label one', 'label two']);
        expect($row)->toBe([$concrete->field_one, $concrete->field_two]);
    });

    test('Excel method with nested sections is working as expected', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        $file_path = DataExtractor::make($concrete)
            ->toXlsx(data: [
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
            ]);

        [$header, $row] = readXlsxSheet($file_path);

        // il campo esterno "field_one" deve conservare la propria colonna
        // (non deve essere sovrascritto dai campi della section annidata)
        expect($header)->toBe(['label one', 'label two', 'label three']);
        expect($row)->toBe([$concrete->field_one, $concrete->field_two, $concrete->field_three]);
    });
});

describe('Array implementation is working', function () {
    test('Excel method is working as expected', function () {
        $concrete = [
            "field_one" => "value one",
            "field_two" => "value two",
        ];

        $file_path = DataExtractor::make($concrete)
            ->toXlsx(data: [
                "type" => "section",
                "label" => "outer section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        [$header, $row] = readXlsxSheet($file_path);

        expect($header)->toBe(['label one', 'label two']);
        expect($row)->toBe([$concrete['field_one'], $concrete['field_two']]);
    });
});

describe('Object implementation is working', function () {
    test('Excel method is working as expected', function () {
        $concrete = json_decode(json_encode([
            "field_one" => "value one",
            "field_two" => "value two",
        ]));

        $file_path = DataExtractor::make($concrete)
            ->toXlsx(data: [
                "type" => "section",
                "label" => "outer section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "field_two" => ["path" => "field_two", "label" => "label two"],
                ],
            ]);

        [$header, $row] = readXlsxSheet($file_path);

        expect($header)->toBe(['label one', 'label two']);
        expect($row)->toBe([$concrete->field_one, $concrete->field_two]);
    });
});
