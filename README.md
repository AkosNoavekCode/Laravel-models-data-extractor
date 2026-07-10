# Laravel Models Data Extractor

Extract information from Eloquent models, plain PHP arrays, or generic objects, based on a declarative **schema**, and render the result as an `IteratorElement` tree, a plain array, JSON, HTML, CSV, or XLSX.

## Installation

```bash
composer require akosnoavek/models-data-extractor
```

The package ships a Laravel service provider (`AkosNoavek\DataExtractor\Support\ServiceProvider`) and a `DataExtractor` facade, both auto-discoverable via the `laravel.providers` / `laravel.aliases` entries in `composer.json`.

## Core concepts

Every extraction involves two independent things:

1. **The target** — the data you are extracting *from*. It can be an Eloquent `Model`, a plain PHP `array`, or a generic `object` (e.g. the result of `json_decode`).
2. **The schema** — a declarative description of *what* to extract and how to label it: which fields to pull out, how to group them into sections, and how to format them.

```php
use AkosNoavek\DataExtractor\Facades\DataExtractor;

$array = DataExtractor::make($target)->toArray(data: $schema);
```

`DataExtractor::make($target)` binds the target; the terminal method (`toArray`, `toJson`, `toHtml`, `toCsv`, `toXlsx`, `extract`) binds the schema and runs the extraction.

## The schema data structure

A schema is a nested associative array (or its JSON equivalent) built from two kinds of nodes: **fields** and **sections**.

### Field

A leaf node that reads a single value out of the target.

```php
[
    "path"  => "field_one",      // required — dot-notation path into the target
    "label" => "Field One",      // required — human-readable label used in output
    "date"  => false,            // optional — format the value as a date (d/m/Y)
    "view"  => null,             // optional — Blade view name for custom HTML rendering
    "evaluate_when_empty" => true, // optional — keep the field even if its value is empty
    "extra_attributes" => null,  // optional — arbitrary array passed through to the output/view
]
```

- `path` supports dot notation for nested properties/relations, e.g. `"path" => "address.city"`.
- `date` parses the resolved value with Carbon and formats it as `d/m/Y` (falls back to an empty string if it can't be parsed).
- `evaluate_when_empty: false` removes the field from the output entirely when its resolved value is empty — useful for optional data.

### Section

A node that groups other fields/sections together under a `label`.

```php
[
    "type"   => "section",       // required — marks this node as a section
    "label"  => "Section label", // required
    "fields" => [                // required, non-empty — child fields/sections, keyed by
                                  // any array key (only used while building the schema)
        "field_one" => ["path" => "field_one", "label" => "Field One"],
        "field_two" => ["path" => "field_two", "label" => "Field Two"],
    ],
]
```

Sections can be nested arbitrarily deep — a section's `fields` array can itself contain other sections.

Internally, every node (field or section) is parsed into an `AkosNoavek\DataExtractor\Iterators\IteratorElement`. The keys of the `fields` array are only used as PHP array keys while you author the schema; they are not part of the output. If a child node doesn't declare its own `label`, its schema key is used as the label.

### Root sections (relations / collections)

A section can pull its target from a relation or nested collection on the *parent* target, instead of the outer target, via `root`:

```php
[
    "type"  => "section",
    "label" => "Manager",
    "root"  => "manager",        // path to a single related model
    "fields" => [
        "manager_name" => ["path" => "name", "label" => "Manager name"],
    ],
]
```

- If `root` resolves to a **single related model** (or a plain array/object), the section's fields are simply resolved against that related record.
- If `root` resolves to a **collection** (e.g. a `hasMany` relation, or an array of arrays), the section is **cloned once per item**, in order, and each clone's fields are resolved against that item. This means a schema with a single `root` section template can expand into N sections/rows in the output.

```php
$parent->setRelation('children', new Collection([$child_one, $child_two]));

$schema = [
    "type" => "section",
    "label" => "outer",
    "fields" => [
        "children_section" => [
            "type" => "section",
            "label" => "Child section",
            "root" => "children",
            "fields" => [
                "child_name" => ["path" => "name", "label" => "Child name"],
            ],
        ],
    ],
];
```

- `toArray()` / `toJson()` / `toHtml()` / `extract()` reflect this naturally: you get one section/row per related record.
- `toCsv()` / `toXlsx()` are flat, single-row formats. When multiple clones share the same label (as they do here, since they come from the same template), their values are merged into a **single column**, joined with `"; "` (e.g. `"Child One; Child Two"`) rather than overwriting one another.

### Multi-value fields (`.*.` path syntax)

A single field can also flatten a to-many relation into one value, without declaring a nested section, using `.*.` in its `path`:

```php
["path" => "children.*.name", "label" => "Children names"]
```

This resolves `name` on every item of the `children` relation/collection and joins them into one string, using the separator configured for the output format (`toHtml`/`toCsv`/`toXlsx`/`toArray` use `"<br>"`).

## Providing the schema: inline array vs. JSON file

Every terminal method (`extract`, `toArray`, `toJson`, `toHtml`, `toCsv`, `toXlsx`) accepts the schema in one of two equivalent ways:

**1. As an inline PHP array**, via the `data` argument:

```php
$result = DataExtractor::make($target)->toArray(data: [
    "type" => "section",
    "label" => "Section label",
    "fields" => [
        "field_one" => ["path" => "field_one", "label" => "Field One"],
    ],
]);
```

Under the hood, the array is `json_encode`d to a temporary file and read back as JSON — so an inline array and a JSON file are handled identically.

**2. As a path to a JSON schema file**, via the `filename` argument:

```php
// schema.json
// {
//     "type": "section",
//     "label": "Section label",
//     "fields": {
//         "field_one": { "path": "field_one", "label": "Field One" }
//     }
// }

$result = DataExtractor::make($target)->toArray(filename: base_path('schemas/schema.json'));
```

You must pass at least one of `data` or `filename` — an exception is thrown if neither is provided. If both are given, `data` takes precedence.

A JSON file can also contain **multiple named schemas** (top-level keys), and you extract a specific one via the `section` argument:

```php
// schema.json: { "employees": { "type": "section", ... }, "managers": { "type": "section", ... } }

DataExtractor::make($target)->toArray(filename: base_path('schemas/schema.json'), section: 'employees');
```

## Working with different target types

The same schema works unchanged against any of the three supported target shapes — only how `path` is resolved differs internally (array key lookup vs. object/model property access):

```php
use Illuminate\Database\Eloquent\Model;

// Eloquent model
$model = SomeModel::find(1);
DataExtractor::make($model)->toArray(data: $schema);

// Plain array
$data = ["field_one" => "value one"];
DataExtractor::make($data)->toArray(data: $schema);

// Generic object (e.g. decoded JSON)
$object = json_decode(json_encode(["field_one" => "value one"]));
DataExtractor::make($object)->toArray(data: $schema);
```

## Output formats

```php
$schema = [
    "type" => "section",
    "label" => "Section label",
    "fields" => [
        "field_one" => ["path" => "field_one", "label" => "Field One"],
        "field_two" => ["path" => "field_two", "label" => "Field Two"],
    ],
];

$el    = DataExtractor::make($target)->extract(data: $schema); // AkosNoavek\DataExtractor\Iterators\IteratorElement tree
$array = DataExtractor::make($target)->toArray(data: $schema); // nested PHP array
$json  = DataExtractor::make($target)->toJson(data: $schema);  // JSON string
$html  = DataExtractor::make($target)->toHtml(data: $schema);  // rendered HTML (Blade)
$path  = DataExtractor::make($target)->toCsv(data: $schema);   // path to a generated .csv file
$path  = DataExtractor::make($target)->toXlsx(data: $schema);  // path to a generated .xlsx file
```

- `extract()` returns the raw `IteratorElement` tree (each element exposes `type`, `label`, `data`, `path`, `fields`, `root`, etc.) — useful when you want to post-process the result yourself.
- `toArray()` / `toJson()` return a nested structure of `{label, value, ...}` leaves grouped under `{label, data: [...]}` sections, mirroring the schema's shape (including root-generated clones).
- `toHtml()` renders an unordered list per section by default; a field/section can opt into a custom Blade `view` for full control over its markup.
- `toCsv()` / `toXlsx()` flatten the whole tree into a single header row + single data row — one column per unique label. See [Root sections](#root-sections-relations--collections) above for how duplicate labels (typically from a collection `root`) are merged.

## Running the tests

```bash
composer test
# or
vendor/bin/pest
```
