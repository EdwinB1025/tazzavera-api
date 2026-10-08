<?php

namespace App\Scribe;

use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

/**
 * EDB 10/08/26: array query parameters documented with PHP's bracket form (AUT review, cataRef).
 *
 * Scribe names an array query parameter after its validation rule (`cataRef`), which
 * OpenAPI clients send as `cataRef=a&cataRef=b`; PHP only reads an array from
 * `cataRef[]=a&cataRef[]=b` (a repeated plain key keeps the last value, and a single
 * one arrives as a string, failing the `array` rule). This generator renames every
 * array query parameter to `name[]` so the contract states what the API accepts.
 */
class BracketArrayQueryParams extends OpenApiGenerator
{
    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        foreach ($pathItem['parameters'] ?? [] as $index => $parameter) {
            if (
                ($parameter['in'] ?? null) === 'query'
                && ($parameter['schema']['type'] ?? null) === 'array'
                && ! str_ends_with($parameter['name'], '[]')
            ) {
                $pathItem['parameters'][$index]['name'] = $parameter['name'] . '[]';
            }
        }

        return $pathItem;
    }
}
