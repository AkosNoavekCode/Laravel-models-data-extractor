<?php

use AkosNoavek\DataExtractor\Facades\DataExtractor;

test('that true is true', function () {
    dd(DataExtractor::test());
    expect(true)->toBe(true);
});
