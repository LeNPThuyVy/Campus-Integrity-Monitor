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
        if (extension_loaded('pdo_sqlsrv')) {
            try {
                return DB::connection('sqlsrv')->getPdo();
            } catch (\Throwable) {
                // Tiếp tục thử kết nối trực tiếp
            }
        }

        $host = (string)env('DB_SERVER', env('DB_HOST', 'localhost\\SQLEXPRESS'));
        $database = (string)env('DB_NAME', env('DB_DATABASE', 'CampusIntegrityMonitor'));
        $trusted = filter_var(env('DB_TRUSTED_CONNECTION', true), FILTER_VALIDATE_BOOLEAN);
        $username = (string)env('DB_USER', env('DB_USERNAME', 'sa'));
        $password = (string)env('DB_PASSWORD', '12345');

        if (extension_loaded('pdo_odbc')) {
            $odbcDrivers = ['ODBC Driver 17 for SQL Server', 'ODBC Driver 18 for SQL Server', 'SQL Server'];
            foreach ($odbcDrivers as $drv) {
                try {
                    if ($trusted) {
                        $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;Trusted_Connection=yes;', $drv, $host, $database);
                        return new PDO($dsn, null, null, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    }

                    $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;', $drv, $host, $database);
                    return new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if (extension_loaded('pdo_sqlsrv')) {
            $encrypt = env('DB_ENCRYPT', 'yes');
            $trustCert = env('DB_TRUST_SERVER_CERTIFICATE', 'yes');
            $dsn = sprintf('sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s', $host, $database, $encrypt, $trustCert);
            return new PDO($dsn, $trusted ? null : $username, $trusted ? null : $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        throw new RuntimeException('Không tìm thấy driver PDO tương thích (pdo_odbc hoặc pdo_sqlsrv) để kết nối SQL Server.');
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
            'HOCKY' => [
                'MA_HK' => $this->code($data['MA_HK'] ?? null, 'học kỳ', 20),
                'TEN_HK' => $this->requiredText($data['TEN_HK'] ?? null, 'TEN_HK', 100),
                'NGAY_BAT_DAU' => $this->requiredText($data['NGAY_BAT_DAU'] ?? null, 'NGAY_BAT_DAU', 10),
                'NGAY_KET_THUC' => $this->requiredText($data['NGAY_KET_THUC'] ?? null, 'NGAY_KET_THUC', 10),
                'HIEN_TAI' => $this->flag($data['HIEN_TAI'] ?? 0, 'HIEN_TAI'),
            ],
            default => throw new InvalidArgumentException('Loại danh mục không hợp lệ.'),
        };
    }

    public function cleanUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            return mb_check_encoding($data, 'UTF-8') ? $data : mb_convert_encoding($data, 'UTF-8', 'Windows-1252');
        }
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->cleanUtf8($v);
            }
            return $data;
        }
        return $data;
    }

    public function decodeHexUnicode(?string $hex, mixed $fallback = ''): string
    {
        if ($hex && ctype_xdigit($hex) && strlen($hex) % 4 === 0) {
            $bin = @hex2bin($hex);
            if ($bin !== false) {
                $utf8 = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16LE');
                if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) {
                $unmojibake = @mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');
                if ($unmojibake !== false && mb_check_encoding($unmojibake, 'UTF-8') && !str_contains($unmojibake, '?')) {
                    if (preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ]/u', $unmojibake)) {
                        return $unmojibake;
                    }
                }
                    return $utf8;
                }
            }
        }
        if ($fallback === null || $fallback === '') {
            return '';
        }
        $val = (string)$fallback;
        return mb_check_encoding($val, 'UTF-8') ? $val : mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
    }

    public function getData(string $entity): array
    {
        $queries = [
            'KHOA' => 'SELECT ID, MA_KHOA, TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX FROM KHOA ORDER BY MA_KHOA',
            'NGANH' => 'SELECT n.ID, n.MA_NGANH, n.TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(n.TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX, k.MA_KHOA, k.TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX
                        FROM NGANH n INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY n.MA_NGANH',
            'LOP' => 'SELECT l.ID, l.MA_LOP, n.MA_NGANH, n.TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(n.TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX, k.MA_KHOA
                      FROM LOP l INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                      INNER JOIN KHOA k ON k.ID = n.KHOA_ID ORDER BY l.MA_LOP',
            'LOI_VIPHAM' => 'SELECT ID, ID as MA_LOI, TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX, DIEM_TRU, 1 as DANG_SU_DUNG FROM LOI_VIPHAM ORDER BY ID',
            'HOCKY' => 'SELECT ID, MA_HK, TEN_HK, CONVERT(VARCHAR(MAX), CAST(TEN_HK AS VARBINARY(MAX)), 2) AS TEN_HK_HEX, NGAY_BAT_DAU, NGAY_KET_THUC, HIEN_TAI FROM HOCKY ORDER BY NGAY_BAT_DAU ASC',
        ];
        if (!isset($queries[$entity])) {
            throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
        }
        $pdo = $this->getPdo();
        $rows = $pdo->query($queries[$entity])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            if (isset($r['TEN_KHOA_HEX'])) {
                $r['TEN_KHOA'] = $this->decodeHexUnicode($r['TEN_KHOA_HEX'], $r['TEN_KHOA'] ?? '');
                unset($r['TEN_KHOA_HEX']);
            }
            if (isset($r['TEN_NGANH_HEX'])) {
                $r['TEN_NGANH'] = $this->decodeHexUnicode($r['TEN_NGANH_HEX'], $r['TEN_NGANH'] ?? '');
                unset($r['TEN_NGANH_HEX']);
            }
            if (isset($r['TEN_LOI_HEX'])) {
                $r['TEN_LOI'] = $this->decodeHexUnicode($r['TEN_LOI_HEX'], $r['TEN_LOI'] ?? '');
                unset($r['TEN_LOI_HEX']);
            }
            if (isset($r['TEN_HK_HEX'])) {
                $r['TEN_HK'] = $this->decodeHexUnicode($r['TEN_HK_HEX'], $r['TEN_HK'] ?? '');
                unset($r['TEN_HK_HEX']);
            }
            if (isset($r['NGAY_BAT_DAU'])) {
                $r['NGAY_BAT_DAU'] = date('Y-m-d', strtotime((string)$r['NGAY_BAT_DAU']));
            }
            if (isset($r['NGAY_KET_THUC'])) {
                $r['NGAY_KET_THUC'] = date('Y-m-d', strtotime((string)$r['NGAY_KET_THUC']));
            }
        }
        return $this->cleanUtf8($rows);
    }

    public function getAllCatalogs(): array
    {
        $entities = ['KHOA', 'NGANH', 'LOP', 'LOI_VIPHAM', 'HOCKY'];
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
                $stmt = $pdo->prepare('INSERT INTO LOI_VIPHAM (TEN_LOI, DIEM_TRU) OUTPUT INSERTED.ID VALUES (?, ?)');
                $stmt->execute([$record['TEN_LOI'], $record['DIEM_TRU']]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE LOI_VIPHAM SET TEN_LOI = ?, DIEM_TRU = ? WHERE ID = ?');
            $stmt->execute([$record['TEN_LOI'], $record['DIEM_TRU'], $id]);
            return (int)$id;
        }

        if ($entity === 'HOCKY') {
            if (!empty($record['HIEN_TAI'])) {
                $pdo->exec('UPDATE HOCKY SET HIEN_TAI = 0');
            }
            if ($id === null) {
                $stmt = $pdo->prepare('INSERT INTO HOCKY (MA_HK, TEN_HK, NGAY_BAT_DAU, NGAY_KET_THUC, HIEN_TAI) OUTPUT INSERTED.ID VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$record['MA_HK'], $record['TEN_HK'], $record['NGAY_BAT_DAU'], $record['NGAY_KET_THUC'], $record['HIEN_TAI']]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE HOCKY SET MA_HK = ?, TEN_HK = ?, NGAY_BAT_DAU = ?, NGAY_KET_THUC = ?, HIEN_TAI = ? WHERE ID = ?');
            $stmt->execute([$record['MA_HK'], $record['TEN_HK'], $record['NGAY_BAT_DAU'], $record['NGAY_KET_THUC'], $record['HIEN_TAI'], $id]);
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
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM CT_VIPHAM WHERE ID_LOI = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Loại vi phạm này đã có sinh viên bị ghi nhận, không thể xóa. Hãy chuyển sang ngừng sử dụng.');
            }
            $del = $pdo->prepare('DELETE FROM LOI_VIPHAM WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        if ($entity === 'HOCKY') {
            $del = $pdo->prepare('DELETE FROM HOCKY WHERE ID = ?');
            $del->execute([$id]);
            return;
        }

        throw new InvalidArgumentException('Loại danh mục không hợp lệ.');
    }
}
