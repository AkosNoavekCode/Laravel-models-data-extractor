<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use Illuminate\Database\Eloquent\Model;

/**
 * Simula una base class "custom" usata da un'applicazione host al posto di
 * Illuminate\Database\Eloquent\Model direttamente (es. un modello Eloquent
 * di dominio condiviso). BuilderIterator riconosce come "modello singolo"
 * solo le classi il cui parent e' Model::class oppure e' elencato in
 * config('data_extractor.model_classes').
 */
abstract class CustomBaseModel extends Model {}

describe('data_extractor.model_classes configuration', function () {
    test('a root pointing to an instance of a registered custom base class is treated as a single related model', function () {
        config(['data_extractor.model_classes' => [CustomBaseModel::class]]);

        $manager = new class extends CustomBaseModel {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $schema = [
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
        ];

        $el = DataExtractor::make($employee)->extract(data: $schema);

        // una sola section (non un clone per "proprieta'" del modello)
        expect($el->fields)->toHaveCount(1);
        expect($el->fields[0]->root)->toBe('manager');
        expect($el->fields[0]->fields[0]->data)->toBe('Mario Rossi');
    });

    test('an unregistered custom base class is NOT recognized as a single model and the section is silently dropped', function () {
        // config('data_extractor.model_classes') di default e' vuoto: senza
        // registrare la classe, BuilderIterator tenta di iterare il modello
        // come fosse una collezione di elementi correlati. Un'istanza di
        // Eloquent Model non e' ne' Traversable ne' espone proprieta'
        // pubbliche, quindi il foreach non produce alcun clone e l'intera
        // section sparisce dall'output, invece di sollevare un errore.
        $manager = new class extends CustomBaseModel {};
        $manager->name = "Mario Rossi";

        $employee = new class extends Model {};
        $employee->setRelation('manager', $manager);

        $schema = [
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
        ];

        $el = DataExtractor::make($employee)->extract(data: $schema);

        expect($el->fields)->toHaveCount(0);
    });
});

describe('data_extractor.date_format configuration', function () {
    test('date fields are formatted using the configured format', function () {
        config(['data_extractor.date_format' => 'Y-m-d']);

        $model = new class extends Model {};
        $model->created_at = '2024-03-15 10:00:00';

        $el = DataExtractor::make($model)->extract(data: [
            "path" => "created_at",
            "label" => "Created at",
            "date" => true,
        ]);

        expect($el->data)->toBe('2024-03-15');
    });

    test('date fields default to d/m/Y when not configured', function () {
        $model = new class extends Model {};
        $model->created_at = '2024-03-15 10:00:00';

        $el = DataExtractor::make($model)->extract(data: [
            "path" => "created_at",
            "label" => "Created at",
            "date" => true,
        ]);

        expect($el->data)->toBe('15/03/2024');
    });
});
