<?php
declare(strict_types=1);

/**
 * Load KEY=VALUE pairs from the shared .env file at the repository root (or the path in
 * CIM_ENV_FILE). Variables already present in the real environment always win, so production
 * servers can configure everything through the web server / OS instead of a file.
 */
function cim_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $path = getenv('CIM_ENV_FILE') ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        if (str_starts_with($name, 'export ')) {
            $name = trim(substr($name, 7));
        }
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            continue;
        }
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        } else {
            $hash = strpos($value, ' #');
            if ($hash !== false) {
                $value = rtrim(substr($value, 0, $hash));
            }
        }
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function cim_env(string $name, ?string $default = null): ?string
{
    cim_load_env();
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function cim_env_bool(string $name, bool $default = false): bool
{
    $value = cim_env($name);
    if ($value === null) {
        return $default;
    }
    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}
