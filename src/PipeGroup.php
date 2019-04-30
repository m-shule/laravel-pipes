<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\Arr;

class PipeGroup
{
    /**
     * Merge route groups into a new array.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    public static function merge($new, $old)
    {
        $new = [
            'namespace' => static::formatNamespace($new, $old),
        ];

        return array_merge_recursive(Arr::except(
            $old,
            ['namespace']
        ), $new);
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return string|null
     */
    protected static function formatNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace']) && 0 !== strpos($new['namespace'], '\\')
                    ? trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
                    : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }
}
