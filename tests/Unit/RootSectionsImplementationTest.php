<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use AkosNoavek\DataExtractor\Iterators\IteratorElement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Le relazioni Eloquent vengono simulate in memoria con setRelation(),
 * senza toccare un database reale: BuilderIterator accede a una relazione
 * gia' caricata tramite la normale property-access di Eloquent
 * ($model->relation), che restituisce il valore preimpostato senza
 * eseguire query.
 */

describe('Root element pointing to a single related model', function () {
    test('HTML method is working as expected', function () {
        $manager = new class extends Model {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $el = DataExtractor::make($employee)
            ->toHtml(data: [
                "type" => "section",
                "label" => "outer",
                "fields" => [
                    "manager_section" => [
                        "type" => "section",
                        "label" => "manager section",
                        "root" => "manager",
                        "fields" => [
                            "manager_name" => ["path" => "name", "label" => "Manager name"],
                        ],
                    ],
                ],
            ]);

        expect($el)->toContain($manager->name);
        expect($el)->toContain('Manager name');
    });

    test('Array method is working as expected', function () {
        $manager = new class extends Model {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $el = DataExtractor::make($employee)
            ->toArray(data: [
                "type" => "section",
                "label" => "outer",
                "fields" => [
                    "manager_section" => [
                        "type" => "section",
                        "label" => "manager section",
                        "root" => "manager",
                        "fields" => [
                            "manager_name" => ["path" => "name", "label" => "Manager name"],
                        ],
                    ],
                ],
            ]);

        expect($el[0]['data'][0]['data'][0]['value'])->toBe($manager->name);
    });

    test('Extract method is working as expected', function () {
        $manager = new class extends Model {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($employee)
            ->extract(data: [
                "type" => "section",
                "label" => "outer",
                "fields" => [
                    "manager_section" => [
                        "type" => "section",
                        "label" => "manager section",
                        "root" => "manager",
                        "fields" => [
                            "manager_name" => ["path" => "name", "label" => "Manager name"],
                        ],
                    ],
                ],
            ]);

        $manager_section = $el->fields[0];
        expect($manager_section->type)->toBe(IteratorElement::SECTION);
        expect($manager_section->root)->toBe('manager');
        expect($manager_section->fields[0]->data)->toBe($manager->name);
    });

    test('CSV method is working as expected', function () {
        $manager = new class extends Model {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $file_path = DataExtractor::make($employee)
            ->toCsv(data: [
                "type" => "section",
                "label" => "outer",
                "fields" => [
                    "manager_section" => [
                        "type" => "section",
                        "label" => "manager section",
                        "root" => "manager",
                        "fields" => [
                            "manager_name" => ["path" => "name", "label" => "Manager name"],
                        ],
                    ],
                ],
            ]);

        $content = file_get_contents($file_path);
        expect($content)->toContain('Manager name');
        expect($content)->toContain($manager->name);
    });

    test('Excel method is working as expected', function () {
        $manager = new class extends Model {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $file_path = DataExtractor::make($employee)
            ->toXlsx(data: [
                "type" => "section",
                "label" => "outer",
                "fields" => [
                    "manager_section" => [
                        "type" => "section",
                        "label" => "manager section",
                        "root" => "manager",
                        "fields" => [
                            "manager_name" => ["path" => "name", "label" => "Manager name"],
                        ],
                    ],
                ],
            ]);

        [$header, $row] = readXlsxSheet($file_path);
        expect($header)->toBe(['Manager name']);
        expect($row)->toBe([$manager->name]);
    });
});

describe('Root element pointing to a collection (one-to-many)', function () {
    test('Model target: repeats the section once per related record, in order', function () {
        $child_one = new class extends Model {};
        $child_one->name = "Child One";
        $child_two = new class extends Model {};
        $child_two->name = "Child Two";

        $parent = new class extends Model {};
        $parent->setRelation('children', new Collection([$child_one, $child_two]));

        $schema = [
            "type" => "section",
            "label" => "outer",
            "fields" => [
                "children_section" => [
                    "type" => "section",
                    "label" => "child section",
                    "root" => "children",
                    "fields" => [
                        "child_name" => ["path" => "name", "label" => "Child name"],
                    ],
                ],
            ],
        ];

        $arr = DataExtractor::make($parent)->toArray(data: $schema);
        expect($arr[0]['data'])->toHaveCount(2);
        expect($arr[0]['data'][0]['data'][0]['value'])->toBe('Child One');
        expect($arr[0]['data'][1]['data'][0]['value'])->toBe('Child Two');

        $json = json_decode(DataExtractor::make($parent)->toJson(data: $schema), true);
        expect($json[0]['data'])->toHaveCount(2);
        expect($json[0]['data'][0]['data'][0]['value'])->toBe('Child One');
        expect($json[0]['data'][1]['data'][0]['value'])->toBe('Child Two');

        $html = DataExtractor::make($parent)->toHtml(data: $schema);
        expect($html)->toContain('Child One');
        expect($html)->toContain('Child Two');

        /**
         * @var IteratorElement $el
         */
        $el = DataExtractor::make($parent)->extract(data: $schema);
        // il template "children_section" viene sostituito da un clone per
        // ogni elemento della relazione, direttamente nei fields dell'outer
        expect($el->fields)->toHaveCount(2);
        expect($el->fields[0]->type)->toBe(IteratorElement::SECTION);
        expect($el->fields[0]->root)->toBe('children');
        expect($el->fields[0]->fields[0]->data)->toBe('Child One');
        expect($el->fields[1]->fields[0]->data)->toBe('Child Two');

        // ogni clone condivide la stessa label di partenza ("Child name"), ma
        // rappresenta un record distinto: CSV ed Excel producono quindi una
        // riga indipendente per ogni clone, nello stesso ordine della relazione.
        $file_path = DataExtractor::make($parent)->toCsv(data: $schema);
        $csv = array_map('str_getcsv', file($file_path));
        [$header, $row_one, $row_two] = $csv;

        expect($header)->toContain('Child name');
        $child_name_index = array_search('Child name', $header);
        expect($row_one[$child_name_index])->toBe('Child One');
        expect($row_two[$child_name_index])->toBe('Child Two');

        // stessa aspettativa per l'export Excel: una riga per clone.
        $xlsx_path = DataExtractor::make($parent)->toXlsx(data: $schema);
        [$xlsx_header, $xlsx_row_one, $xlsx_row_two] = readXlsxSheet($xlsx_path);

        expect($xlsx_header)->toBe(['Child name']);
        expect($xlsx_row_one)->toBe(['Child One']);
        expect($xlsx_row_two)->toBe(['Child Two']);
    });

    test('Array target: repeats the section once per related record, in order', function () {
        $concrete = [
            "items" => [
                ["name" => "Item One"],
                ["name" => "Item Two"],
            ],
        ];

        $schema = [
            "type" => "section",
            "label" => "outer",
            "fields" => [
                "items_section" => [
                    "type" => "section",
                    "label" => "items section",
                    "root" => "items",
                    "fields" => [
                        "item_name" => ["path" => "name", "label" => "Item name"],
                    ],
                ],
            ],
        ];

        $arr = DataExtractor::make($concrete)->toArray(data: $schema);
        expect($arr[0]['data'])->toHaveCount(2);
        expect($arr[0]['data'][0]['data'][0]['value'])->toBe('Item One');
        expect($arr[0]['data'][1]['data'][0]['value'])->toBe('Item Two');
    });

    test('Object target: repeats the section once per related record, in order', function () {
        $concrete = json_decode(json_encode([
            "items" => [
                ["name" => "Item One"],
                ["name" => "Item Two"],
            ],
        ]));

        $schema = [
            "type" => "section",
            "label" => "outer",
            "fields" => [
                "items_section" => [
                    "type" => "section",
                    "label" => "items section",
                    "root" => "items",
                    "fields" => [
                        "item_name" => ["path" => "name", "label" => "Item name"],
                    ],
                ],
            ],
        ];

        $arr = DataExtractor::make($concrete)->toArray(data: $schema);
        expect($arr[0]['data'])->toHaveCount(2);
        expect($arr[0]['data'][0]['data'][0]['value'])->toBe('Item One');
        expect($arr[0]['data'][1]['data'][0]['value'])->toBe('Item Two');
    });
});

describe('Root elements combined with nested sections', function () {
    test('a plain (non-root) outer section can contain an inner section with a collection root', function () {
        $child_one = new class extends Model {};
        $child_one->name = "Child One";
        $child_two = new class extends Model {};
        $child_two->name = "Child Two";

        $parent = new class extends Model {};
        $parent->field_one = "value one";
        $parent->setRelation('children', new Collection([$child_one, $child_two]));

        $schema = [
            "type" => "section",
            "label" => "outer",
            "fields" => [
                "field_one" => ["path" => "field_one", "label" => "label one"],
                "children_section" => [
                    "type" => "section",
                    "label" => "child section",
                    "root" => "children",
                    "fields" => [
                        "child_name" => ["path" => "name", "label" => "Child name"],
                    ],
                ],
            ],
        ];

        $arr = DataExtractor::make($parent)->toArray(data: $schema);

        // field_one resta il primo elemento della sezione esterna (non innestato)
        expect($arr[0]['data'][0]['value'])->toBe('value one');

        // seguono i due blocchi ripetuti, uno per figlio, nell'ordine della relazione
        expect($arr[0]['data'])->toHaveCount(3);
        expect($arr[0]['data'][1]['data'][0]['value'])->toBe('Child One');
        expect($arr[0]['data'][2]['data'][0]['value'])->toBe('Child Two');

        // il campo "field_one" della section esterna deve mantenere la propria
        // colonna anche quando e' seguito da una section con root a piu' elementi,
        // ed essere replicato su ogni riga generata dai due cloni.
        $xlsx_path = DataExtractor::make($parent)->toXlsx(data: $schema);
        [$xlsx_header, $xlsx_row_one, $xlsx_row_two] = readXlsxSheet($xlsx_path);

        expect($xlsx_header)->toBe(['label one', 'Child name']);
        expect($xlsx_row_one)->toBe(['value one', 'Child One']);
        expect($xlsx_row_two)->toBe(['value one', 'Child Two']);
    });
});
