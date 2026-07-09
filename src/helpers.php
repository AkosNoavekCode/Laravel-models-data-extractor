<?php

if (! function_exists('safe_value')) {
  /**
   * Return the value if the property/array key exists, otherwise return null.
   */
  function safe_value(mixed $value, string $key): mixed
  {
    // The incoming key is always in dot notation
    $keys = explode('.', $key);

    // If the value is an array, check if the key exists
    if (is_array($value)) {
      $temp = $value;

      foreach ($keys as $key) {
        $temp = $temp[$key] ?? null;
      }

      return $temp;
    }

    // If the value is an object, check if the property exists
    if (! is_array($value) && !empty($value)) {
      $temp = $value;

      foreach ($keys as $key) {
        $temp = $temp?->$key ?? null;
      }

      return $temp;
    }

    return null;
  }
}
