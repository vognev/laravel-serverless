<?php

namespace Laravel\Serverless;

use Illuminate\Support\Str;

class Config
{
    /**
     * Returns a list of php modules to include when building runtime
     * @return array
     */
    public static function phpModules() : array
    {
        $modules = config('serverless.php.modules');
        $presets = config('serverless.php.presets');

        $idx = 0;

        while ($idx < count($modules)) {
            if (array_key_exists($modules[$idx], $presets)) {
                array_splice($modules, $idx, 1, $presets[$modules[$idx]]);
                continue;
            }

            $idx++;
        }

        return $modules;
    }

    public static function projectSlug(string $postfix = null)
    {
        $slug = basename(base_path());

        if (! is_null($postfix)) {
            $slug .= '_' . Str::slug($postfix);
        }

        return $slug;
    }
}
