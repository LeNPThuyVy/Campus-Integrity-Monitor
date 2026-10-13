<?php
declare(strict_types=1);

require_once __DIR__ . '/student_module.php';

function catalog_required_text(mixed $value, string $field, int $limit): string
{
    if (!is_string($value)) {
        throw new InvalidArgumentException("Trường {$field} không hợp lệ.");
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value, 'UTF-8') > $limit) {
        throw new InvalidArgumentException("Trường {$field} bắt buộc và tối đa {$limit} ký tự.");
    }
    return $value;
}

function catalog_code(mixed $value, string $field, int $limit): string
{
    $value = catalog_required_text($value, $field, $limit);
    if (preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
        throw new InvalidArgumentException("Mã {$field} chỉ được chứa chữ ASCII, số, _ hoặc -.");
    }
    return $value;
}

function catalog_record(string $entity, array $data): array
{
    return match ($entity) {
        'KHOA' => [
            'MA_KHOA' => catalog_code($data['MA_KHOA'] ?? null, 'khoa', 10),
            'TEN_KHOA' => catalog_required_text($data['TEN_KHOA'] ?? null, 'TEN_KHOA', 50),
        ],
        'NGANH' => [
            'MA_NGANH' => catalog_code($data['MA_NGANH'] ?? null, 'ngành', 10),
            'TEN_NGANH' => catalog_required_text($data['TEN_NGANH'] ?? null, 'TEN_NGANH', 50),
            'MA_KHOA' => catalog_code($data['MA_KHOA'] ?? null, 'khoa', 10),
        ],
        'LOP' => [
            'MA_LOP' => catalog_code($data['MA_LOP'] ?? null, 'lớp', 20),
            'MA_NGANH' => catalog_code($data['MA_NGANH'] ?? null, 'ngành', 10),
        ],
        'LOI_VIPHAM' => [
            'TEN_LOI' => catalog_required_text($data['TEN_LOI'] ?? null, 'TEN_LOI', 100),
            'DIEM_TRU' => catalog_int($data['DIEM_TRU'] ?? null, 'DIEM_TRU'),
        ],
        default => throw new InvalidArgumentException('Loại danh mục không hợp lệ.'),
    };
}

function catalog_int(mixed $value, string $field): int
{
    if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
        throw new InvalidArgumentException("Trường {$field} phải là số nguyên.");
    }
    $normalized = (string)$value;
    $negative = str_starts_with($normalized, '-');
    $digits = ltrim(ltrim($normalized, '-'), '0');
    $digits = $digits === '' ? '0' : $digits;
    if (strlen($digits) > 10) {
        throw new InvalidArgumentException("Trường {$field} vượt phạm vi SQL Server INT.");
    }
    $normalized = ($negative && $digits !== '0' ? '-' : '') . $digits;
    $integer = filter_var($normalized, FILTER_VALIDATE_INT);
    if ($integer === false || $integer < -2147483648 || $integer > 2147483647) {
        throw new InvalidArgumentException("Trường {$field} vượt phạm vi SQL Server INT.");
    }
    return $integer;
}

function catalog_data(PDO $pdo, string $entity): array
{
    $queries = [
        'KHOA' => 'SELECT ID, MA_KHOA, TEN_KHOA FROM KHOA ORDER BY MA_KHOA',
        'NGANH' => 'SELECT n.ID, n.MA_NGANH, n.TEN_NGANH, k.MA_KHOA, k.TEN_KHOA
                    FROM NGANH n INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY n.MA_NGANH',
        'LOP' => 'SELECT l.ID, l.MA_LOP, n.MA_NGANH, n.TEN_NGANH, k.MA_KHOA
                  FROM LOP l INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                  INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY l.MA_LOP',
        'LOI_VIPHAM' => 'SELECT ID, TEN_LOI, DIEM_TRU FROM LOI_VIPHAM ORDER BY ID',
    ];
    if (!isset($queries[$entity])) {
        throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
    }
    return $pdo->query($queries[$entity])->fetchAll();
}

function catalog_save(PDO $pdo, string $entity, mixed $rawId, array $rawData): int
{
    $data = catalog_record($entity, $rawData);
    $id = null;
    if ($rawId !== null && $rawId !== '') {
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            throw new InvalidArgumentException('ID danh mục không hợp lệ.');
        }
    }

    $foreignKeys = [
        'NGANH' => ['MA_KHOA', 'SELECT ID FROM KHOA WHERE MA_KHOA = :code'],
        'LOP' => ['MA_NGANH', 'SELECT ID FROM NGANH WHERE MA_NGANH = :code'],
    ];
    if (isset($foreignKeys[$entity])) {
        [$field, $sql] = $foreignKeys[$entity];
        $lookup = $pdo->prepare($sql);
        $lookup->execute(['code' => $data[$field]]);
        $foreignKeyId = $lookup->fetchColumn();
        if ($foreignKeyId === false) {
            throw new InvalidArgumentException("{$field}: mã tham chiếu không tồn tại.");
        }
        if ($entity === 'NGANH') {
            $data['KHOA_ID'] = (int)$foreignKeyId;
            unset($data['MA_KHOA']);
        } else {
            $data['NGANH_ID'] = (int)$foreignKeyId;
            unset($data['MA_NGANH']);
        }
    }

    $definition = [
        'KHOA' => ['table' => 'KHOA', 'fields' => ['MA_KHOA', 'TEN_KHOA']],
        'NGANH' => ['table' => 'NGANH', 'fields' => ['MA_NGANH', 'TEN_NGANH', 'KHOA_ID']],
        'LOP' => ['table' => 'LOP', 'fields' => ['MA_LOP', 'NGANH_ID']],
        'LOI_VIPHAM' => ['table' => 'LOI_VIPHAM', 'fields' => ['TEN_LOI', 'DIEM_TRU']],
    ][$entity];

    if ($id === null) {
        $columns = $definition['fields'];
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $sql = 'INSERT INTO ' . $definition['table'] . ' (' . implode(', ', $columns) . ')'
            . ' OUTPUT INSERTED.ID VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $pdo->prepare($sql);
        foreach ($columns as $column) {
            $statement->bindValue(':' . $column, $data[$column],
                is_int($data[$column]) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();
        return (int)$statement->fetchColumn();
    }

    $assignments = array_map(
        static fn(string $column): string => $column . ' = :' . $column,
        $definition['fields']
    );
    $sql = 'UPDATE ' . $definition['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE ID = :id';
    $statement = $pdo->prepare($sql);
    foreach ($definition['fields'] as $column) {
        $statement->bindValue(':' . $column, $data[$column],
            is_int($data[$column]) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM ' . $definition['table'] . ' WHERE ID = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            throw new InvalidArgumentException('Không tìm thấy danh mục cần cập nhật.');
        }
    }
    return $id;
}

function catalog_delete(PDO $pdo, string $entity, mixed $rawId): void
{
    $tables = [
        'KHOA' => 'KHOA',
        'NGANH' => 'NGANH',
        'LOP' => 'LOP',
        'LOI_VIPHAM' => 'LOI_VIPHAM',
    ];
    if (!isset($tables[$entity])) {
        throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
    }
    $id = filter_var($rawId, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        throw new InvalidArgumentException('ID danh mục không hợp lệ.');
    }
    $statement = $pdo->prepare('DELETE FROM ' . $tables[$entity] . ' WHERE ID = :id');
    $statement->execute(['id' => $id]);
    if ($statement->rowCount() === 0) {
        throw new InvalidArgumentException('Không tìm thấy danh mục cần xóa.');
    }
}
