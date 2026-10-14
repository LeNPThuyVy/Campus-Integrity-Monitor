<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/ui/includes/catalog_module.php';

function catalog_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function catalog_expect_invalid(callable $operation): void
{
    try {
        $operation();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException('Expected catalog validation to reject the value.');
}

$checks = 0;
catalog_check(
    catalog_record('KHOA', ['MA_KHOA' => 'CNTT', 'TEN_KHOA' => 'Công nghệ thông tin'])
        === ['MA_KHOA' => 'CNTT', 'TEN_KHOA' => 'Công nghệ thông tin'],
    'Catalog validation must preserve Vietnamese names.'
);
$checks++;
catalog_check(catalog_record('NGANH', [
    'MA_NGANH' => 'KHDL',
    'TEN_NGANH' => 'Khoa học dữ liệu',
    'MA_KHOA' => 'CNTT',
])['MA_KHOA'] === 'CNTT', 'Major must map to a faculty by unique code.');
$checks++;
catalog_check(catalog_record('LOP', [
    'MA_LOP' => 'CNTT01',
    'MA_NGANH' => 'CNTT',
])['MA_NGANH'] === 'CNTT', 'Class must map to a major by unique code.');
$checks++;
catalog_check(catalog_record('LOI_VIPHAM', [
    'TEN_LOI' => 'Thiếu thẻ sinh viên',
    'DIEM_TRU' => '-5',
])['DIEM_TRU'] === -5, 'Penalty points must be stored as the SQL INT value without conversion.');
$checks++;
catalog_expect_invalid(static fn() => catalog_record('KHOA', [
    'MA_KHOA' => 'MÃ KHOA',
    'TEN_KHOA' => 'Tên khoa',
]));
$checks++;
catalog_expect_invalid(static fn() => catalog_int('2147483648', 'DIEM_TRU'));
$checks++;

echo "Catalog module unit tests passed: {$checks} checks.\n";
