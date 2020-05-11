<?php

namespace Mshule\LaravelPipes;

use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Arr;

class PipeGroup extends RouteGroup
{
    /**
     * Merge route groups into a new array.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    public static function merge($new, $old, $prependExistingPrefix = true)
    {
        $new = array_merge($new, [
            'namespace' => static::formatNamespace($new, $old),
            'key' => static::formatKey($new, $old),
            'where' => static::formatWhere($new, $old),
        ]);

        return array_merge_recursive(Arr::except(
            $old,
            ['namespace', 'key', 'where']
        ), $new);
    }

    /**
     * Format the attributes of the new group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return string|null
     */
    public static function formatKey($new, $old)
    {
        return isset($new['key'])
            ? $new['key']
            : ($old['key'] ?? null);
    }
}
