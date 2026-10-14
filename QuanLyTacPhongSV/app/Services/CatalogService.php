<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class CatalogService
{
    public function getPdo(): PDO
    {
        try {
            return DB::connection('sqlsrv')->getPdo();
        } catch (\Throwable $e) {
            // Fallback: direct PDO connection if Laravel sqlsrv driver configuration differs
            $host = env('DB_SERVER', env('DB_HOST', 'localhost\SQLEXPRESS'));
            $database = env('DB_NAME', env('DB_DATABASE', 'CampusIntegrityMonitor'));
            $trusted = filter_var(env('DB_TRUSTED_CONNECTION', true), FILTER_VALIDATE_BOOLEAN);
            $username = env('DB_USER', env('DB_USERNAME', ''));
            $password = env('DB_PASSWORD', '');
            $encrypt = env('DB_ENCRYPT', 'yes');
            $trustCert = env('DB_TRUST_SERVER_CERTIFICATE', 'yes');

            $dsn = sprintf(
                'sqlsrv:Server={%s};Database={%s};Encrypt=%s;TrustServerCertificate=%s',
                str_replace('}', '}}', (string)$host),
                str_replace('}', '}}', (string)$database),
                $encrypt,
                $trustCert
            );

            return new PDO($dsn, $trusted ? null : $username, $trusted ? null : $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    }

    public function requiredText(mixed $value, string $field, int $limit): string
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

    public function code(mixed $value, string $field, int $limit): string
    {
        $value = $this->requiredText($value, $field, $limit);
        if (preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            throw new InvalidArgumentException("Mã {$field} chỉ được chứa chữ ASCII, số, _ hoặc -.");
        }
        return $value;
    }

    public function flag(mixed $value, string $field): int
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 1;
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 0;
        }
        throw new InvalidArgumentException("Trường {$field} chỉ nhận 0 hoặc 1.");
    }

    public function intVal(mixed $value, string $field): int
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
        return (int)$integer;
    }

    public function sanitizeRecord(string $entity, array $data): array
    {
        return match ($entity) {
            'KHOA' => [
                'MA_KHOA' => $this->code($data['MA_KHOA'] ?? null, 'khoa', 10),
                'TEN_KHOA' => $this->requiredText($data['TEN_KHOA'] ?? null, 'TEN_KHOA', 50),
            ],
            'NGANH' => [
                'MA_NGANH' => $this->code($data['MA_NGANH'] ?? null, 'ngành', 10),
                'TEN_NGANH' => $this->requiredText($data['TEN_NGANH'] ?? null, 'TEN_NGANH', 50),
                'MA_KHOA' => $this->code($data['MA_KHOA'] ?? null, 'khoa', 10),
            ],
            'LOP' => [
                'MA_LOP' => $this->code($data['MA_LOP'] ?? null, 'lớp', 20),
                'MA_NGANH' => $this->code($data['MA_NGANH'] ?? null, 'ngành', 10),
            ],
            'LOI_VIPHAM' => [
                'TEN_LOI' => $this->requiredText($data['TEN_LOI'] ?? null, 'TEN_LOI', 100),
                'DIEM_TRU' => $this->intVal($data['DIEM_TRU'] ?? null, 'DIEM_TRU'),
                'DANG_SU_DUNG' => $this->flag($data['DANG_SU_DUNG'] ?? 1, 'DANG_SU_DUNG'),
            ],
            default => throw new InvalidArgumentException('Loại danh mục không hợp lệ.'),
        };
    }

    public function getData(string $entity): array
    {
        $queries = [
            'KHOA' => 'SELECT ID, MA_KHOA, TEN_KHOA FROM KHOA ORDER BY MA_KHOA',
            'NGANH' => 'SELECT n.ID, n.MA_NGANH, n.TEN_NGANH, k.MA_KHOA, k.TEN_KHOA
                        FROM NGANH n INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY n.MA_NGANH',
            'LOP' => 'SELECT l.ID, l.MA_LOP, n.MA_NGANH, n.TEN_NGANH, k.MA_KHOA
                      FROM LOP l INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                      INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY l.MA_LOP',
            'LOI_VIPHAM' => 'SELECT ID, MA_LOI, TEN_LOI, DIEM_TRU, DANG_SU_DUNG FROM LOI_VIPHAM ORDER BY ID',
        ];
        if (!isset($queries[$entity])) {
            throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
        }
        $pdo = $this->getPdo();
        return $pdo->query($queries[$entity])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCatalogs(): array
    {
        $entities = ['KHOA', 'NGANH', 'LOP', 'LOI_VIPHAM'];
        $result = [];
        foreach ($entities as $entity) {
            $result[$entity] = $this->getData($entity);
        }
        return $result;
    }

    public function save(string $entity, mixed $rawId, array $rawData): int
    {
        $pdo = $this->getPdo();
        $record = $this->sanitizeRecord($entity, $rawData);
        $id = $rawId === null || $rawId === '' ? null : filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($rawId !== null && $rawId !== '' && $id === false) {
            throw new InvalidArgumentException('ID danh mục không hợp lệ.');
        }

        if ($entity === 'KHOA') {
            if ($id === null) {
                $stmt = $pdo->prepare('INSERT INTO KHOA (MA_KHOA, TEN_KHOA) OUTPUT INSERTED.ID VALUES (?, ?)');
                $stmt->execute([$record['MA_KHOA'], $record['TEN_KHOA']]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE KHOA SET MA_KHOA = ?, TEN_KHOA = ? WHERE ID = ?');
            $stmt->execute([$record['MA_KHOA'], $record['TEN_KHOA'], $id]);
            return (int)$id;
        }

        if ($entity === 'NGANH') {
            $parent = $pdo->prepare('SELECT ID FROM KHOA WHERE MA_KHOA = ?');
            $parent->execute([$record['MA_KHOA']]);
            $khoaId = $parent->fetchColumn();
            if ($khoaId === false) {
                throw new InvalidArgumentException('Khoa không tồn tại.');
            }
            if ($id === null) {
                $stmt = $pdo->prepare('INSERT INTO NGANH (MA_NGANH, TEN_NGANH, KHOA_ID) OUTPUT INSERTED.ID VALUES (?, ?, ?)');
                $stmt->execute([$record['MA_NGANH'], $record['TEN_NGANH'], (int)$khoaId]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE NGANH SET MA_NGANH = ?, TEN_NGANH = ?, KHOA_ID = ? WHERE ID = ?');
            $stmt->execute([$record['MA_NGANH'], $record['TEN_NGANH'], (int)$khoaId, $id]);
            return (int)$id;
        }

        if ($entity === 'LOP') {
            $parent = $pdo->prepare('SELECT ID FROM NGANH WHERE MA_NGANH = ?');
            $parent->execute([$record['MA_NGANH']]);
            $nganhId = $parent->fetchColumn();
            if ($nganhId === false) {
                throw new InvalidArgumentException('Ngành không tồn tại.');
            }
            if ($id === null) {
                $stmt = $pdo->prepare('INSERT INTO LOP (MA_LOP, NGANH_ID) OUTPUT INSERTED.ID VALUES (?, ?)');
                $stmt->execute([$record['MA_LOP'], (int)$nganhId]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE LOP SET MA_LOP = ?, NGANH_ID = ? WHERE ID = ?');
            $stmt->execute([$record['MA_LOP'], (int)$nganhId, $id]);
            return (int)$id;
        }

        if ($entity === 'LOI_VIPHAM') {
            if ($id === null) {
                $maxStmt = $pdo->query('SELECT ISNULL(MAX(MA_LOI), 0) + 1 FROM LOI_VIPHAM');
                $nextMaLoi = (int)$maxStmt->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO LOI_VIPHAM (MA_LOI, TEN_LOI, DIEM_TRU, DANG_SU_DUNG) OUTPUT INSERTED.ID VALUES (?, ?, ?, ?)');
                $stmt->execute([$nextMaLoi, $record['TEN_LOI'], $record['DIEM_TRU'], $record['DANG_SU_DUNG']]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE LOI_VIPHAM SET TEN_LOI = ?, DIEM_TRU = ?, DANG_SU_DUNG = ? WHERE ID = ?');
            $stmt->execute([$record['TEN_LOI'], $record['DIEM_TRU'], $record['DANG_SU_DUNG'], $id]);
            return (int)$id;
        }

        throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
    }

    public function delete(string $entity, mixed $rawId): void
    {
        $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException('ID danh mục không hợp lệ.');
        }
        $pdo = $this->getPdo();

        if ($entity === 'KHOA') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM NGANH WHERE KHOA_ID = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Khoa này còn ngành trực thuộc, không thể xóa.');
            }
            $del = $pdo->prepare('DELETE FROM KHOA WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        if ($entity === 'NGANH') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM LOP WHERE NGANH_ID = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Ngành này còn lớp trực thuộc, không thể xóa.');
            }
            $del = $pdo->prepare('DELETE FROM NGANH WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        if ($entity === 'LOP') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM SINHVIEN WHERE LOP_ID = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Lớp này còn sinh viên, không thể xóa.');
            }
            $del = $pdo->prepare('DELETE FROM LOP WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        if ($entity === 'LOI_VIPHAM') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM CT_VIPHAM WHERE LOI_ID = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Loại vi phạm này đã có sinh viên bị ghi nhận, không thể xóa. Hãy chuyển sang ngừng sử dụng.');
            }
            $del = $pdo->prepare('DELETE FROM LOI_VIPHAM WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
    }
}
