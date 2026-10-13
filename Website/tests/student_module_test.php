<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/ui/includes/student_module.php';

final class StudentTestStatement extends PDOStatement
{
    private int $affectedRows;

    public function __construct(
        private StudentTestPDO $database,
        private string $sql,
        private array $rows = [],
        private mixed $scalar = null,
        int $affectedRows = 1
    ) {
        $this->affectedRows = $affectedRows;
    }

    public function execute(?array $params = null): bool
    {
        if (str_starts_with($this->sql, 'INSERT INTO KHOA') ||
            str_starts_with($this->sql, 'INSERT INTO NGANH') ||
            str_starts_with($this->sql, 'INSERT INTO LOP') ||
            str_starts_with($this->sql, 'INSERT INTO SINHVIEN')) {
            $this->database->recordInsert();
        }
        return true;
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return true;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        if ($mode === PDO::FETCH_COLUMN) {
            return array_map(static fn($row) => is_array($row) ? reset($row) : $row, $this->rows);
        }
        return $this->rows;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->scalar;
    }

    public function rowCount(): int
    {
        return $this->affectedRows;
    }
}

final class StudentTestPDO extends PDO
{
    public bool $transaction = false;
    public int $commits = 0;
    public int $rollbacks = 0;
    public int $inserts = 0;
    public ?int $failInsertAt = null;

    public function __construct()
    {
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $rows = match (true) {
            str_contains($query, 'SELECT MA_SV FROM SINHVIEN') => ['SV001'],
            str_contains($query, 'SELECT MA_LOP FROM LOP') => ['CLS01'],
            str_contains($query, 'SELECT MA_KHOA FROM KHOA') => ['K01'],
            str_contains($query, 'SELECT TEN_KHOA FROM KHOA') => ['Khoa đã tồn tại'],
            str_contains($query, 'SELECT MA_NGANH FROM NGANH') => ['NG01'],
            str_contains($query, 'SELECT TEN_NGANH FROM NGANH') => ['Ngành đã tồn tại'],
            default => [],
        };
        return new StudentTestStatement($this, $query, $rows);
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return match (true) {
            str_contains($query, 'SELECT l.ID') => new StudentTestStatement($this, $query, [], 7),
            str_contains($query, 'OUTPUT INSERTED.ID') => new StudentTestStatement($this, $query, [], 101),
            default => new StudentTestStatement($this, $query),
        };
    }

    public function exec(string $statement): int|false
    {
        return 0;
    }

    public function beginTransaction(): bool
    {
        $this->transaction = true;
        return true;
    }

    public function commit(): bool
    {
        $this->transaction = false;
        $this->commits++;
        return true;
    }

    public function rollBack(): bool
    {
        $this->transaction = false;
        $this->rollbacks++;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->transaction;
    }

    public function recordInsert(): void
    {
        $this->inserts++;
        if ($this->failInsertAt === $this->inserts) {
            throw new PDOException('Simulated database failure.');
        }
    }
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectWorkbookError(callable $operation, string $fragment): void
{
    try {
        $operation();
    } catch (StudentWorkbookException $error) {
        check(str_contains($error->getMessage(), $fragment), 'Unexpected workbook error message.');
        return;
    }
    throw new RuntimeException('Expected workbook validation to fail.');
}

function temporaryWorkbook(array $headers, array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'cim-test-');
    if ($path === false) {
        throw new RuntimeException('Could not create a temporary test workbook.');
    }
    file_put_contents($path, student_create_xlsx($headers, $rows));
    return $path;
}

$checks = 0;
$validKhoa = ['MA_KHOA' => 'K02', 'TEN_KHOA' => 'Khoa Công nghệ thông tin'];
$pdo = new StudentTestPDO();
$preview = student_import_preview($pdo, 'KHOA', [
    ['row' => 2, 'values' => $validKhoa],
]);
check($preview[0]['status'] === 'valid', 'Vietnamese faculty name should validate.');
$checks++;

$studentRows = [
    ['row' => 2, 'values' => [
        'MA_SV' => 'SV002', 'HOTEN' => 'Nguyễn Thuỳ Ánh',
        'NIEN_KHOA' => '2021', 'MA_LOP' => 'CLS01',
    ]],
    ['row' => 3, 'values' => [
        'MA_SV' => 'SV001', 'HOTEN' => 'Tên trùng MSSV',
        'NIEN_KHOA' => '2021', 'MA_LOP' => 'CLS01',
    ]],
    ['row' => 4, 'values' => [
        'MA_SV' => 'SV003', 'HOTEN' => 'Mã lớp không tồn tại',
        'NIEN_KHOA' => '2021', 'MA_LOP' => 'MISSING',
    ]],
    ['row' => 5, 'values' => [
        'MA_SV' => '', 'HOTEN' => 'Thiếu mã sinh viên',
        'NIEN_KHOA' => '2021', 'MA_LOP' => 'CLS01',
    ]],
];
$preview = student_import_preview($pdo, 'SINHVIEN', $studentRows);
check($preview[0]['status'] === 'valid', 'Valid Vietnamese student row should pass.');
check($preview[1]['status'] === 'duplicate', 'Duplicate MSSV should be skipped.');
check($preview[2]['status'] === 'error' && str_contains(implode(' ', $preview[2]['errors']), 'MA_LOP'),
    'Missing class foreign key should be reported.');
check($preview[3]['status'] === 'error' && str_contains(implode(' ', $preview[3]['errors']), 'MA_SV'),
    'Missing required MSSV should be reported.');
$checks += 4;

check(student_year(14) === 14 && student_year('15') === 15,
    'NIEN_KHOA values must be preserved as stored instead of reinterpreted as years.');
$checks++;
$legacyYearPreview = student_import_preview($pdo, 'SINHVIEN', [[
    'row' => 2,
    'values' => [
        'MA_SV' => 'SV014',
        'HOTEN' => 'Sinh viên niên khóa 14',
        'NIEN_KHOA' => '14',
        'MA_LOP' => 'CLS01',
    ],
]]);
check($legacyYearPreview[0]['status'] === 'valid' &&
    $legacyYearPreview[0]['values']['NIEN_KHOA'] === 14,
    'XLSX import must preserve NIEN_KHOA 14 as the SQL INT value 14.');
$checks++;
check(student_parse_year_value('2147483648') === null,
    'Out-of-range SQL Server INT values must be rejected.');
$checks++;

$pdo->failInsertAt = null;
$imported = student_run_import_transaction($pdo, 'KHOA', [
    ['status' => 'valid', 'values' => ['MA_KHOA' => 'K02', 'TEN_KHOA' => 'Khoa']],
    ['status' => 'duplicate', 'values' => ['MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khoa cũ']],
]);
check($imported === 1 && $pdo->commits === 1 && $pdo->rollbacks === 0,
    'Import should insert valid rows and commit once while skipping duplicates.');
$checks++;

$pdo->inserts = 0;
$pdo->failInsertAt = 2;
try {
    student_run_import_transaction($pdo, 'KHOA', [
        ['status' => 'valid', 'values' => ['MA_KHOA' => 'K03', 'TEN_KHOA' => 'Khoa 3']],
        ['status' => 'valid', 'values' => ['MA_KHOA' => 'K04', 'TEN_KHOA' => 'Khoa 4']],
    ]);
    throw new RuntimeException('Expected simulated mid-import failure.');
} catch (PDOException) {
    check($pdo->rollbacks === 1 && !$pdo->transaction, 'Mid-import failure must roll back the transaction.');
}
$checks++;
$pdo->failInsertAt = null;

$created = student_upsert($pdo, [
    'ma_sv' => 'SV100',
    'ho_ten' => 'Đặng Thị Mai',
    'nien_khoa' => 2021,
    'ma_khoa' => 'K01',
    'ma_nganh' => 'NG01',
    'ma_lop' => 'CLS01',
]);
check($created['id'] === 101 && $created['ma_sv'] === 'SV100',
    'Student creation should use the SQL Server identity result.');
$updated = student_upsert($pdo, [
    'id' => 101,
    'ma_sv' => 'SV101',
    'ho_ten' => 'Đặng Thị Mai',
    'nien_khoa' => '2022',
    'ma_khoa' => 'K01',
    'ma_nganh' => 'NG01',
    'ma_lop' => 'CLS01',
]);
check($updated['id'] === 101 && $updated['ma_sv'] === 'SV101',
    'Student update should return the existing database identity.');
student_delete($pdo, 101);
$checks += 2;

$path = temporaryWorkbook(
    ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'],
    [['SV002', 'Nguyễn Thuỳ Ánh', '2021', 'CLS01']]
);
try {
    $parsed = student_parse_xlsx($path, 'SINHVIEN');
    check($parsed[0]['values']['HOTEN'] === 'Nguyễn Thuỳ Ánh', 'XLSX should preserve Vietnamese Unicode.');
    $checks++;
} finally {
    unlink($path);
}

$path = temporaryWorkbook(['MA_KHOA', 'TEN_SAI'], []);
try {
    expectWorkbookError(
        static fn() => student_parse_xlsx($path, 'KHOA'),
        'Header không khớp'
    );
    $checks++;
} finally {
    unlink($path);
}

$path = temporaryWorkbook(['MA_KHOA', 'TEN_KHOA'], []);
try {
    expectWorkbookError(
        static fn() => student_parse_xlsx($path, 'KHOA'),
        'không có dòng dữ liệu'
    );
    $checks++;
} finally {
    unlink($path);
}

$path = temporaryWorkbook(['MA_KHOA', 'TEN_KHOA'], [['2', 'Khoa thử kiểu số']]);
$zip = new ZipArchive();
$zip->open($path);
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$sheet = str_replace(
    '<c r="A2" t="inlineStr"><is><t xml:space="preserve">2</t></is></c>',
    '<c r="A2" t="n"><v>2</v></c>',
    $sheet
);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
$zip->close();
try {
    $parsed = student_parse_xlsx($path, 'KHOA');
    $typedPreview = student_import_preview($pdo, 'KHOA', $parsed);
    check($typedPreview[0]['status'] === 'error' &&
        str_contains(implode(' ', $typedPreview[0]['errors']), 'MA_KHOA'),
        'Codes stored as numbers should produce a column-specific preview error.');
    $checks++;
} finally {
    unlink($path);
}

$formulaPath = temporaryWorkbook(['MA_KHOA', 'TEN_KHOA'], [['K02', 'Khoa']]);
$zip = new ZipArchive();
$zip->open($formulaPath);
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$sheet = str_replace('<c r="B2" t="inlineStr">', '<c r="B2" t="inlineStr"><f>1+1</f>', $sheet);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
$zip->close();
try {
    expectWorkbookError(static fn() => student_parse_xlsx($formulaPath, 'KHOA'), 'công thức');
    $checks++;
} finally {
    unlink($formulaPath);
}

$path = tempnam(sys_get_temp_dir(), 'cim-bad-');
file_put_contents($path, 'not an xlsx');
try {
    expectWorkbookError(static fn() => student_parse_xlsx($path, 'KHOA'), 'XLSX hợp lệ');
    $checks++;
} finally {
    unlink($path);
}

$safeWorkbook = student_create_xlsx(['MA_KHOA', 'TEN_KHOA'], [['K01', '=HYPERLINK("x")']]);
$safePath = tempnam(sys_get_temp_dir(), 'cim-safe-');
file_put_contents($safePath, $safeWorkbook);
$zip = new ZipArchive();
$zip->open($safePath);
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();
check(!str_contains($sheet, '<f>') && str_contains($sheet, '&apos;=HYPERLINK'),
    'Export must neutralize formula-like cell values.');
unlink($safePath);
$checks++;

echo "Student module unit tests passed: {$checks} checks.\n";
