<?php
declare(strict_types=1);

/*
 * Manual violation records (CT_VIPHAM) against a real SQL Server TEST database.
 *   CIM_TEST_DB_NAME=CampusIntegrityMonitor_Test DB_SERVER=.\SQLEXPRESS DB_TRUSTED_CONNECTION=yes \
 *   DB_TRUST_SERVER_CERTIFICATE=yes php tests/student_violation_db_test.php
 * Skipped without CIM_TEST_DB_NAME. Everything runs inside one transaction that is rolled back.
 */
$testDb = getenv('CIM_TEST_DB_NAME') ?: '';
if ($testDb === '' || stripos($testDb, 'test') === false) {
    echo "SKIPPED student violation DB tests: set CIM_TEST_DB_NAME (name must contain 'test').\n";
    exit(0);
}
putenv('DB_NAME=' . $testDb);
putenv('CIM_ENV_FILE=' . __DIR__ . '/__no_env_file__');

require_once dirname(__DIR__) . '/ui/includes/student_module.php';

function db_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $message);
    }
}

function expect_error(callable $operation, int $status, ?string $code = null): void
{
    try {
        $operation();
    } catch (StudentRequestException $error) {
        db_check($error->httpStatus === $status, "expected HTTP {$status}, got {$error->httpStatus}: {$error->getMessage()}");
        db_check($code === null || $error->errorCode === $code, "expected code {$code}");
        return;
    }
    throw new RuntimeException("FAILED: expected StudentRequestException {$status}");
}

$pdo = student_db();
db_check(student_violation_schema_ready($pdo), 'migration 001 must be applied to the test database');
$today = new DateTimeImmutable('today');
$checks = 0;
$pdo->beginTransaction();
try {
    $studentId = (int)$pdo->query("SELECT ID FROM SINHVIEN WHERE MA_SV = 'SV001'")->fetchColumn();
    $typeId = (int)$pdo->query("SELECT ID FROM LOI_VIPHAM WHERE MA_LOI = 'NO_CARD'")->fetchColumn();
    $before = count(student_violation_list($pdo, $studentId));
    $base = ['student_id' => $studentId, 'type_id' => $typeId, 'date' => $today->format('Y-m-d'), 'note' => 'db test'];

    $first = student_violation_add($pdo, $base, $today);
    db_check($first['id'] > 0, 'insert returns the new ID');
    $checks++;

    expect_error(static fn() => student_violation_add($pdo, $base, $today), 409, 'duplicate_same_day');
    $checks++;

    $second = student_violation_add($pdo, $base + ['confirm_duplicate' => true], $today);
    db_check($second['id'] !== $first['id'], 'confirmed repeat gets its own ID');
    $rows = student_violation_list($pdo, $studentId);
    db_check(count($rows) === $before + 2, 'two separate records of the same type on the same day');
    $checks++;

    $yesterday = student_violation_add($pdo, ['date' => $today->modify('-1 day')->format('Y-m-d')] + $base, $today);
    db_check($yesterday['id'] > 0, 'same type on another day needs no confirmation');
    $checks++;

    expect_error(static fn() => student_violation_add($pdo, ['date' => $today->modify('+1 day')->format('Y-m-d')] + $base, $today), 422);
    expect_error(static fn() => student_violation_add($pdo, ['date' => '2026-02-30'] + $base, $today), 422);
    expect_error(static fn() => student_violation_add($pdo, ['date' => '1999-12-31'] + $base, $today), 422);
    expect_error(static fn() => student_violation_add($pdo, ['student_id' => 99999999] + $base, $today), 404);
    expect_error(static fn() => student_violation_add($pdo, ['type_id' => 99999999] + $base, $today), 422);
    expect_error(static fn() => student_violation_add($pdo, ['note' => str_repeat('x', 501)] + $base, $today), 422);
    expect_error(static fn() => student_violation_add($pdo, ['student_id' => 'abc'] + $base, $today), 422);
    $checks += 7;

    $pdo->exec("UPDATE LOI_VIPHAM SET DANG_SU_DUNG = 0 WHERE ID = {$typeId}");
    expect_error(static fn() => student_violation_add($pdo, ['date' => '2026-01-05'] + $base, $today), 422);
    $pdo->exec("UPDATE LOI_VIPHAM SET DANG_SU_DUNG = 1 WHERE ID = {$typeId}");
    $checks++;

    $otherStudent = (int)$pdo->query("SELECT ID FROM SINHVIEN WHERE MA_SV = 'SV002'")->fetchColumn();
    expect_error(static fn() => student_violation_delete($pdo, $first['id'], $otherStudent), 404);
    db_check(count(student_violation_list($pdo, $studentId)) === $before + 3, 'wrong owner must not delete');
    $checks++;

    student_violation_delete($pdo, $first['id'], $studentId);
    db_check(count(student_violation_list($pdo, $studentId)) === $before + 2, 'delete removes exactly one record');
    expect_error(static fn() => student_violation_delete($pdo, $first['id'], $studentId), 404);
    $checks++;

    $active = array_column(student_violation_types($pdo), 'ID');
    db_check(in_array((string)$typeId, array_map('strval', $active), true), 'active types listed');
    $checks++;
} finally {
    $pdo->rollBack();
}
echo "Student violation DB tests passed: {$checks} checks (transaction rolled back).\n";
