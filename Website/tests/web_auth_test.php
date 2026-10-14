<?php
declare(strict_types=1);

// Unit tests for the Web integration helpers (no database or API required).
$envFile = tempnam(sys_get_temp_dir(), 'cim-env-');
file_put_contents($envFile, implode("\n", [
    '# comment line',
    'CIM_TEST_PLAIN=value one',
    'CIM_TEST_QUOTED="quoted # not a comment"',
    "CIM_TEST_SINGLE='single'",
    'CIM_TEST_INLINE=abc # trailing comment',
    'export CIM_TEST_EXPORTED=yes',
    'INVALID-NAME=ignored',
    'CIM_TEST_EXISTING=from-file',
]));
putenv('CIM_ENV_FILE=' . $envFile);
putenv('CIM_TEST_EXISTING=from-process');

require_once dirname(__DIR__) . '/ui/includes/auth.php';
require_once dirname(__DIR__) . '/ui/includes/catalog_module.php';

function web_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$checks = 0;
web_check(cim_env('CIM_TEST_PLAIN') === 'value one', '.env plain value');
$checks++;
web_check(cim_env('CIM_TEST_QUOTED') === 'quoted # not a comment', '.env quoted value keeps #');
$checks++;
web_check(cim_env('CIM_TEST_SINGLE') === 'single', '.env single quotes');
$checks++;
web_check(cim_env('CIM_TEST_INLINE') === 'abc', '.env inline comment stripped');
$checks++;
web_check(cim_env_bool('CIM_TEST_EXPORTED') === true, '.env export prefix + bool');
$checks++;
web_check(cim_env('CIM_TEST_EXISTING') === 'from-process', 'process environment must win over .env');
$checks++;
web_check(cim_env('INVALID-NAME') === null, 'invalid variable names are ignored');
$checks++;
web_check(cim_env('CIM_TEST_MISSING', 'fallback') === 'fallback', 'default value');
$checks++;

// Open-redirect protection after login.
web_check(cim_safe_next('list_vipham.php') === 'list_vipham.php', 'local page allowed');
$checks++;
foreach (['https://evil.example/', '//evil.example', 'login.php', '../home.php', 'javascript:alert(1)', null] as $bad) {
    web_check(cim_safe_next($bad) === 'home.php', 'unsafe next must fall back to home.php: ' . var_export($bad, true));
    $checks++;
}

// LIKE escaping uses "!" (a backslash breaks PDO named parameters on SQL Server).
web_check(student_bind_like('100%_[x]!') === '100!%!_![x]!!', 'LIKE wildcards are escaped with !');
$checks++;

// Violation type status flag.
web_check(catalog_flag('1', 'X') === 1 && catalog_flag(0, 'X') === 0 && catalog_flag(true, 'X') === 1, 'flag parsing');
$checks++;
try {
    catalog_flag('yes', 'X');
    throw new LogicException('flag must reject other values');
} catch (InvalidArgumentException) {
    $checks++;
}
web_check(catalog_record('LOI_VIPHAM', ['TEN_LOI' => 'Sai đồng phục', 'DIEM_TRU' => 5, 'DANG_SU_DUNG' => '0'])['DANG_SU_DUNG'] === 0,
    'violation type can be deactivated');
$checks++;

web_check(cim_h('<b>"x"</b>') === '&lt;b&gt;&quot;x&quot;&lt;/b&gt;', 'HTML escaping');
$checks++;

unlink($envFile);
echo "Web auth/integration unit tests passed: {$checks} checks.\n";
