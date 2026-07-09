<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;
use Illuminate\Database\Eloquent\Model;

test('that true is true', function () {

    $model = new class extends Model {};
    $concrete = new $model();
    $concrete->field = "value";

    expect(true)->toBe(true);
});
