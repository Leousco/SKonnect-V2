<?php

class AIConfig
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        foreach ([$_ENV, $_SERVER] as $source) {
            if (isset($source[$key]) && is_string($source[$key]) && $source[$key] !== '') {
                return $source[$key];
            }
        }

        $file = self::fileValues();

        return isset($file[$key]) && $file[$key] !== '' ? (string) $file[$key] : $default;
    }

    private static function fileValues(): array
    {
        static $values = null;
        if ($values !== null) {
            return $values;
        }

        $values = [];
        $root = dirname(__DIR__);

        foreach ([$root . '/.env', dirname($root) . '/.env'] as $envPath) {
            if (!is_file($envPath)) {
                continue;
            }
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                [$name, $val] = explode('=', $line, 2);
                $name = trim($name);
                if (strpos($name, 'export ') === 0) {
                    $name = trim(substr($name, 7));
                }
                $val = trim($val);
                if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                    $val = substr($val, 1, -1);
                }
                $values[$name] = $val;
            }
            break;
        }

        $localPath = $root . '/config/ai.local.php';
        if (is_file($localPath)) {
            $values = array_merge($values, (array) require $localPath);
        }

        return $values;
    }
}