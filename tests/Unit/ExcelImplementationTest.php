<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use Illuminate\Database\Eloquent\Collection;
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

    test('Excel method throws when a nested section has no root', function () {
        $model = new class extends Model {};
        $concrete = new $model();
        $concrete->field_one = "value one";
        $concrete->field_two = "value two";
        $concrete->field_three = "value three";

        // una section annidata senza "root" produrrebbe un solo blocco di valori:
        // inutile in un export tabellare, i suoi campi vanno dichiarati
        // direttamente sulla section padre.
        $call = fn () => DataExtractor::make($concrete)
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

        expect($call)->toThrow(Exception::class, "nested section \"inner section label\" has no 'root'");
    });

    test('Excel method with a rooted nested section is working as expected', function () {
        $child_one = new class extends Model {};
        $child_one->name = "Child One";
        $child_two = new class extends Model {};
        $child_two->name = "Child Two";

        $concrete = new class extends Model {};
        $concrete->field_one = "value one";
        $concrete->setRelation('children', new Collection([$child_one, $child_two]));

        $file_path = DataExtractor::make($concrete)
            ->toXlsx(data: [
                "type" => "section",
                "label" => "outer section label",
                "fields" => [
                    "field_one" => ["path" => "field_one", "label" => "label one"],
                    "children_section" => [
                        "type" => "section",
                        "label" => "children section label",
                        "root" => "children",
                        "fields" => [
                            "child_name" => ["path" => "name", "label" => "label two"],
                        ],
                    ],
                ],
            ]);

        [$header, $row_one, $row_two] = readXlsxSheet($file_path);

        // il campo esterno "field_one" deve conservare la propria colonna e
        // comparire su ogni riga generata dalla section annidata con root
        expect($header)->toBe(['label one', 'label two']);
        expect($row_one)->toBe([$concrete->field_one, 'Child One']);
        expect($row_two)->toBe([$concrete->field_one, 'Child Two']);
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
