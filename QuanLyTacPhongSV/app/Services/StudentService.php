<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use SimpleXMLElement;
use Throwable;
use ZipArchive;

class StudentService
{
    public const PAGE_SIZE_DEFAULT = 10;
    public const PAGE_SIZE_MAX = 100;
    public const IMPORT_MAX_BYTES = 15 * 1024 * 1024;
    public const IMPORT_MAX_ROWS = 5000;
    public const PREVIEW_TTL = 1800;
    public const VIOLATION_MIN_DATE = '2000-01-01';

    public function getPdo(): PDO
    {
        if (extension_loaded('pdo_sqlsrv')) {
            try {
                return DB::connection('sqlsrv')->getPdo();
            } catch (Throwable) {
                // Tiếp tục thử kết nối trực tiếp
            }
        }

        $host = (string)config('cim.db.server');
        $database = (string)config('cim.db.name');
        $trusted = filter_var(config('cim.db.trusted'), FILTER_VALIDATE_BOOLEAN);
        $username = (string)config('cim.db.user');
        $password = (string)config('cim.db.password');

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
                } catch (Throwable) {
                    continue;
                }
            }
        }

        if (extension_loaded('pdo_sqlsrv')) {
            $encrypt = config('cim.db.encrypt');
            $trustCert = config('cim.db.trust_server_certificate');
            $dsn = sprintf('sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s', $host, $database, $encrypt, $trustCert);
            return new PDO($dsn, $trusted ? null : $username, $trusted ? null : $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        throw new RuntimeException('Không tìm thấy driver PDO tương thích (pdo_odbc hoặc pdo_sqlsrv) để kết nối SQL Server.');
    }

    public function today(): DateTimeImmutable
    {
        $minutes = (int)config('cim.utc_offset_minutes');
        $offset = sprintf('%s%02d:%02d', $minutes < 0 ? '-' : '+', intdiv(abs($minutes), 60), abs($minutes) % 60);
        return new DateTimeImmutable('today', new DateTimeZone($offset));
    }

    public function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_', '['], ['!!', '!%', '!_', '!['], $value);
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

    public function getCatalog(): array
    {
        $pdo = $this->getPdo();
        $faculties = $pdo->query('SELECT ID, MA_KHOA, TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX FROM KHOA ORDER BY MA_KHOA')->fetchAll();
        foreach ($faculties as &$f) {
            $f['TEN_KHOA'] = $this->decodeHexUnicode($f['TEN_KHOA_HEX'] ?? null, $f['TEN_KHOA']);
            unset($f['TEN_KHOA_HEX']);
        }
        $majors = $pdo->query(
            'SELECT n.ID, n.MA_NGANH, n.TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(n.TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX, n.KHOA_ID, k.MA_KHOA
             FROM NGANH n INNER JOIN KHOA k ON k.ID = n.KHOA_ID
             ORDER BY n.MA_NGANH'
        )->fetchAll();
        foreach ($majors as &$m) {
            $m['TEN_NGANH'] = $this->decodeHexUnicode($m['TEN_NGANH_HEX'] ?? null, $m['TEN_NGANH']);
            unset($m['TEN_NGANH_HEX']);
        }
        $classes = $pdo->query(
            'SELECT l.ID, l.MA_LOP, l.NGANH_ID, n.MA_NGANH, n.KHOA_ID, k.MA_KHOA
             FROM LOP l
             INNER JOIN NGANH n ON n.ID = l.NGANH_ID
             INNER JOIN KHOA k ON k.ID = n.KHOA_ID
             ORDER BY l.MA_LOP'
        )->fetchAll();

        return $this->cleanUtf8(['faculties' => $faculties, 'majors' => $majors, 'classes' => $classes]);
    }

    public function listStudents(array $input): array
    {
        $pdo = $this->getPdo();
        $page = max(1, (int)($input['page'] ?? 1));
        $pageSize = min(self::PAGE_SIZE_MAX, max(1, (int)($input['page_size'] ?? self::PAGE_SIZE_DEFAULT)));

        $where = [];
        $params = [];
        $search = trim((string)($input['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(s.MA_SV LIKE :search_code ESCAPE '!' OR s.HOTEN LIKE :search_name ESCAPE '!')";
            $params['search_code'] = '%' . $this->escapeLike($search) . '%';
            $params['search_name'] = '%' . $this->escapeLike($search) . '%';
        }

        foreach ([
            'faculty' => 'k.MA_KHOA',
            'major' => 'n.MA_NGANH',
            'class' => 'l.MA_LOP',
        ] as $key => $column) {
            $value = trim((string)($input[$key] ?? ''));
            if ($value !== '') {
                $where[] = $column . ' = :' . $key;
                $params[$key] = $value;
            }
        }

        $base = ' FROM SINHVIEN s
                  INNER JOIN LOP l ON l.ID = s.LOP_ID
                  INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                  INNER JOIN KHOA k ON k.ID = n.KHOA_ID';
        $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $pdo->prepare('SELECT COUNT_BIG(*)' . $base . $filter);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $offset = ($page - 1) * $pageSize;

        $sql = 'SELECT s.ID, s.MA_SV, s.HOTEN, CONVERT(VARCHAR(MAX), CAST(s.HOTEN AS VARBINARY(MAX)), 2) AS HOTEN_HEX, s.NIEN_KHOA,
                       l.MA_LOP, n.MA_NGANH, n.TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(n.TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX,
                       k.MA_KHOA, k.TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX,
                       (SELECT COUNT_BIG(*) FROM CT_VIPHAM v WHERE v.ID_SV = s.ID) AS SO_VI_PHAM'
            . $base . $filter
            . " ORDER BY s.MA_SV OFFSET {$offset} ROWS FETCH NEXT {$pageSize} ROWS ONLY";

        $query = $pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $query->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        $query->execute();
        $rows = $query->fetchAll();
        foreach ($rows as &$r) {
            $r['HOTEN'] = $this->decodeHexUnicode($r['HOTEN_HEX'] ?? null, $r['HOTEN']);
            $r['TEN_KHOA'] = $this->decodeHexUnicode($r['TEN_KHOA_HEX'] ?? null, $r['TEN_KHOA']);
            $r['TEN_NGANH'] = $this->decodeHexUnicode($r['TEN_NGANH_HEX'] ?? null, $r['TEN_NGANH'] ?? '');
            unset($r['HOTEN_HEX'], $r['TEN_KHOA_HEX'], $r['TEN_NGANH_HEX']);
        }

        return $this->cleanUtf8([
            'rows' => $rows,
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'pages' => max(1, (int)ceil($total / $pageSize)),
        ]);
    }

    public function getStatistics(): array
    {
        $pdo = $this->getPdo();
        return [
            'students' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM SINHVIEN')->fetchColumn(),
            'faculties' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM KHOA')->fetchColumn(),
            'classes' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM LOP')->fetchColumn(),
        ];
    }

    public function getStudentDetail(int $id): ?array
    {
        $pdo = $this->getPdo();
        $statement = $pdo->prepare(
            'SELECT s.ID, s.MA_SV, s.HOTEN, CONVERT(VARCHAR(MAX), CAST(s.HOTEN AS VARBINARY(MAX)), 2) AS HOTEN_HEX, s.NIEN_KHOA,
                    l.MA_LOP, n.MA_NGANH, k.MA_KHOA, k.TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX
             FROM SINHVIEN s
             INNER JOIN LOP l ON l.ID = s.LOP_ID
             INNER JOIN NGANH n ON n.ID = l.NGANH_ID
             INNER JOIN KHOA k ON k.ID = n.KHOA_ID
             WHERE s.ID = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if ($row) {
            $row['HOTEN'] = $this->decodeHexUnicode($row['HOTEN_HEX'] ?? null, $row['HOTEN']);
            $row['TEN_KHOA'] = $this->decodeHexUnicode($row['TEN_KHOA_HEX'] ?? null, $row['TEN_KHOA']);
            unset($row['HOTEN_HEX'], $row['TEN_KHOA_HEX']);
        }
        return $row ? $this->cleanUtf8($row) : null;
    }

    public function findStudentByCode(string $maSv): ?array
    {
        $code = trim($maSv);
        if ($code === '') {
            return null;
        }
        $pdo = $this->getPdo();
        $stmt = $pdo->prepare(
            'SELECT s.ID, s.MA_SV, s.HOTEN, CONVERT(VARCHAR(MAX), CAST(s.HOTEN AS VARBINARY(MAX)), 2) AS HOTEN_HEX, s.NIEN_KHOA,
                    l.MA_LOP, n.MA_NGANH, n.TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(n.TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX,
                    k.MA_KHOA, k.TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX,
                    (SELECT COUNT_BIG(*) FROM CT_VIPHAM v WHERE v.ID_SV = s.ID) AS SO_VI_PHAM
             FROM SINHVIEN s
             INNER JOIN LOP l ON l.ID = s.LOP_ID
             INNER JOIN NGANH n ON n.ID = l.NGANH_ID
             INNER JOIN KHOA k ON k.ID = n.KHOA_ID
             WHERE UPPER(s.MA_SV) = UPPER(:code)'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['HOTEN'] = $this->decodeHexUnicode($row['HOTEN_HEX'] ?? null, $row['HOTEN']);
        $row['TEN_KHOA'] = $this->decodeHexUnicode($row['TEN_KHOA_HEX'] ?? null, $row['TEN_KHOA']);
        $row['TEN_NGANH'] = $this->decodeHexUnicode($row['TEN_NGANH_HEX'] ?? null, $row['TEN_NGANH'] ?? '');
        unset($row['HOTEN_HEX'], $row['TEN_KHOA_HEX'], $row['TEN_NGANH_HEX']);
        return $this->cleanUtf8($row);
    }

    public function upsertStudent(array $data): array
    {
        $pdo = $this->getPdo();
        $studentId = $data['id'] ?? null;
        $code = trim((string)($data['ma_sv'] ?? ''));
        $name = trim((string)($data['ho_ten'] ?? ''));
        $year = !empty($data['nien_khoa']) ? (int)$data['nien_khoa'] : null;
        $classCode = trim((string)($data['ma_lop'] ?? ''));
        $facultyCode = !empty($data['ma_khoa']) ? trim((string)$data['ma_khoa']) : null;
        $majorCode = !empty($data['ma_nganh']) ? trim((string)$data['ma_nganh']) : null;

        if ($code === '' || $name === '' || $classCode === '') {
            throw new InvalidArgumentException('MSSV, họ và tên và mã lớp là bắt buộc.');
        }

        // Find class ID
        $sql = 'SELECT l.ID FROM LOP l
                INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                INNER JOIN KHOA k ON k.ID = n.KHOA_ID
                WHERE l.MA_LOP = :class';
        if ($facultyCode !== null) {
            $sql .= ' AND k.MA_KHOA = :faculty';
        }
        if ($majorCode !== null) {
            $sql .= ' AND n.MA_NGANH = :major';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':class', $classCode);
        if ($facultyCode !== null) {
            $stmt->bindValue(':faculty', $facultyCode);
        }
        if ($majorCode !== null) {
            $stmt->bindValue(':major', $majorCode);
        }
        $stmt->execute();
        $classId = $stmt->fetchColumn();
        if ($classId === false) {
            throw new InvalidArgumentException('Mã lớp không tồn tại hoặc không thuộc khoa đã chọn.');
        }

        if ($studentId === null || $studentId === '') {
            $insert = $pdo->prepare(
                'INSERT INTO SINHVIEN (MA_SV, HOTEN, NIEN_KHOA, LOP_ID)
                 OUTPUT INSERTED.ID
                 VALUES (:code, :name, :year, :class_id)'
            );
            $insert->execute([
                'code' => $code,
                'name' => $name,
                'year' => $year,
                'class_id' => (int)$classId,
            ]);
            return ['id' => (int)$insert->fetchColumn(), 'ma_sv' => $code];
        }

        $id = (int)$studentId;
        $update = $pdo->prepare(
            'UPDATE SINHVIEN SET MA_SV = :code, HOTEN = :name, NIEN_KHOA = :year, LOP_ID = :class_id
             WHERE ID = :id'
        );
        $update->execute([
            'code' => $code,
            'name' => $name,
            'year' => $year,
            'class_id' => (int)$classId,
            'id' => $id,
        ]);
        return ['id' => $id, 'ma_sv' => $code];
    }

    public function deleteStudent(int $id): void
    {
        $pdo = $this->getPdo();
        $delete = $pdo->prepare('DELETE FROM SINHVIEN WHERE ID = :id');
        $delete->execute(['id' => $id]);
        if ($delete->rowCount() === 0) {
            throw new InvalidArgumentException('Không tìm thấy hồ sơ sinh viên.');
        }
    }

    // ---------------------------------------------------------------- Manual Violations (CT_VIPHAM)
    public function ensureViolationColumns(PDO $pdo): void
    {
        try {
            $check = $pdo->query("SELECT COL_LENGTH('dbo.CT_VIPHAM', 'THOI_DIEM')")->fetchColumn();
            if ($check === null || $check === false) {
                $pdo->exec("ALTER TABLE dbo.CT_VIPHAM ADD THOI_DIEM DATETIME2(0) NULL;");
                $pdo->exec("UPDATE dbo.CT_VIPHAM SET THOI_DIEM = DATEADD(MINUTE, 450, CAST(NGAY_VIPHAM AS DATETIME2(0))) WHERE THOI_DIEM IS NULL;");
            }
            $checkArea = $pdo->query("SELECT COL_LENGTH('dbo.CT_VIPHAM', 'KHU_VUC_ID')")->fetchColumn();
            if ($checkArea === null || $checkArea === false) {
                $pdo->exec("ALTER TABLE dbo.CT_VIPHAM ADD KHU_VUC_ID INT NULL;");
            }
            $checkHk = $pdo->query("SELECT COL_LENGTH('dbo.CT_VIPHAM', 'MA_HK')")->fetchColumn();
            if ($checkHk === null || $checkHk === false) {
                $pdo->exec("ALTER TABLE dbo.CT_VIPHAM ADD MA_HK VARCHAR(20) NULL;");
            }
        } catch (Throwable) {
            // Ignore if already altered or no DDL permissions
        }
    }

    public function resolveSemesterCodeByDate(PDO $pdo, string $date): ?string
    {
        try {
            $stmt = $pdo->prepare('SELECT TOP 1 MA_HK FROM HOCKY WHERE :d BETWEEN NGAY_BAT_DAU AND NGAY_KET_THUC ORDER BY NGAY_BAT_DAU DESC');
            $stmt->execute(['d' => $date]);
            $code = $stmt->fetchColumn();
            return $code ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public function getViolations(int $studentId): array
    {
        $pdo = $this->getPdo();
        $this->ensureViolationColumns($pdo);
        $statement = $pdo->prepare(
            'SELECT v.ID, v.ID_LOI, l.TEN_LOI, CONVERT(VARCHAR(MAX), CAST(l.TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX, 
                    l.DIEM_TRU, v.NGAY_VIPHAM, v.THOI_DIEM, v.KHU_VUC_ID, k.TEN_KHU_VUC, 
                    CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) AS TEN_KHU_VUC_HEX,
                    v.GHI_CHU, CONVERT(VARCHAR(MAX), CAST(v.GHI_CHU AS VARBINARY(MAX)), 2) AS GHI_CHU_HEX,
                    v.MA_HK, hk.TEN_HK, CONVERT(VARCHAR(MAX), CAST(hk.TEN_HK AS VARBINARY(MAX)), 2) AS TEN_HK_HEX
             FROM CT_VIPHAM v 
             INNER JOIN LOI_VIPHAM l ON l.ID = v.ID_LOI
             LEFT JOIN KHU_VUC k ON k.ID = v.KHU_VUC_ID
             LEFT JOIN HOCKY hk ON hk.MA_HK = v.MA_HK
             WHERE v.ID_SV = :student
             ORDER BY COALESCE(v.THOI_DIEM, CAST(v.NGAY_VIPHAM AS DATETIME2)) DESC, v.ID DESC'
        );
        $statement->execute(['student' => $studentId]);
        $vRows = $statement->fetchAll();
        foreach ($vRows as &$vr) {
            $vr['TEN_LOI'] = $this->decodeHexUnicode($vr['TEN_LOI_HEX'] ?? null, $vr['TEN_LOI']);
            $vr['TEN_KHU_VUC'] = $this->decodeHexUnicode($vr['TEN_KHU_VUC_HEX'] ?? null, $vr['TEN_KHU_VUC'] ?? '');
            $vr['GHI_CHU'] = $this->decodeHexUnicode($vr['GHI_CHU_HEX'] ?? null, $vr['GHI_CHU']);
            $vr['TEN_HK'] = $this->decodeHexUnicode($vr['TEN_HK_HEX'] ?? null, $vr['TEN_HK'] ?? '');
            unset($vr['TEN_LOI_HEX'], $vr['TEN_KHU_VUC_HEX'], $vr['GHI_CHU_HEX'], $vr['TEN_HK_HEX']);
        }
        return $vRows;
    }

    public function getViolationTypes(): array
    {
        $pdo = $this->getPdo();
        $vtRows = $pdo->query('SELECT ID, TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX, DIEM_TRU FROM LOI_VIPHAM WHERE DANG_SU_DUNG = 1 ORDER BY ID')->fetchAll();
        foreach ($vtRows as &$vtr) {
            $vtr['TEN_LOI'] = $this->decodeHexUnicode($vtr['TEN_LOI_HEX'] ?? null, $vtr['TEN_LOI']);
            unset($vtr['TEN_LOI_HEX']);
        }
        return $vtRows;
    }

    public function getAreas(): array
    {
        try {
            $pdo = $this->getPdo();
            $rows = $pdo->query('SELECT ID, MA_KHU_VUC, TEN_KHU_VUC, CONVERT(VARCHAR(MAX), CAST(TEN_KHU_VUC AS VARBINARY(MAX)), 2) AS TEN_KHU_VUC_HEX FROM KHU_VUC WHERE DANG_SU_DUNG = 1 ORDER BY ID')->fetchAll();
            foreach ($rows as &$r) {
                $r['TEN_KHU_VUC'] = $this->decodeHexUnicode($r['TEN_KHU_VUC_HEX'] ?? null, $r['TEN_KHU_VUC']);
                unset($r['TEN_KHU_VUC_HEX']);
            }
            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    public function matchAreaIdByName(string $text): ?int
    {
        try {
            $pdo = $this->getPdo();
            $areas = $pdo->query('SELECT ID, MA_KHU_VUC, TEN_KHU_VUC, CONVERT(VARCHAR(MAX), CAST(TEN_KHU_VUC AS VARBINARY(MAX)), 2) AS TEN_KHU_VUC_HEX FROM KHU_VUC')->fetchAll();
            $cleanText = mb_strtolower(trim($text), 'UTF-8');
            foreach ($areas as $a) {
                $name = mb_strtolower($this->decodeHexUnicode($a['TEN_KHU_VUC_HEX'] ?? null, $a['TEN_KHU_VUC']), 'UTF-8');
                $code = mb_strtolower((string)$a['MA_KHU_VUC'], 'UTF-8');
                if ($name !== '' && (str_contains($cleanText, $name) || str_contains($name, $cleanText))) {
                    return (int)$a['ID'];
                }
                if ($code !== '' && str_contains($cleanText, $code)) {
                    return (int)$a['ID'];
                }
            }
        } catch (Throwable) {}
        return null;
    }

    public function parseViolationDateTime(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Số serial Excel (ví dụ 45678.3125) là giờ địa phương "trên đồng hồ", không phải UTC:
        // dựng lại đúng ngày giờ đó, không đổi múi giờ.
        if (is_numeric($raw)) {
            $num = (float)$raw;
            if ($num > 30000 && $num < 70000) {
                $seconds = (int)round(($num - 25569) * 86400);
                $dt = (new DateTimeImmutable('@' . $seconds))->setTimezone(new DateTimeZone('UTC'));
                return $this->dateTimeResult($dt->format('Y-m-d'), $dt->format('H:i'));
            }
        }

        $str = trim((string)$raw);
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'Y/m/d H:i:s',
            'Y/m/d H:i',
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($formats as $fmt) {
            // '!' đặt các trường không có trong format về 0; getLastErrors() báo ngày tràn như 31/02.
            $dt = DateTimeImmutable::createFromFormat('!' . $fmt, $str);
            $errors = DateTimeImmutable::getLastErrors();
            if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                continue;
            }
            $hasTime = str_contains($fmt, 'H');
            return $this->dateTimeResult($dt->format('Y-m-d'), $hasTime ? $dt->format('H:i') : '07:30');
        }

        // Regex fallback: ngày/tháng/năm [giờ:phút] hoặc năm/tháng/ngày [giờ:phút]
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(?:\s+(\d{1,2}):(\d{1,2}))?/', $str, $m)) {
            [$y, $mo, $d] = [(int)$m[3], (int)$m[2], (int)$m[1]];
        } elseif (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2}))?/', $str, $m)) {
            [$y, $mo, $d] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        } else {
            return null;
        }
        $h = isset($m[4]) ? (int)$m[4] : 7;
        $i = isset($m[5]) ? (int)$m[5] : 30;
        if (!checkdate($mo, $d, $y) || $h > 23 || $i > 59) {
            return null;
        }
        return $this->dateTimeResult(sprintf('%04d-%02d-%02d', $y, $mo, $d), sprintf('%02d:%02d', $h, $i));
    }

    private function dateTimeResult(string $date, string $time): array
    {
        return [
            'date' => $date,
            'time' => $time,
            'datetime' => "{$date} {$time}:00",
        ];
    }

    public function addViolation(array $data): array
    {
        $pdo = $this->getPdo();
        $this->ensureViolationColumns($pdo);
        $studentId = (int)($data['student_id'] ?? 0);
        $studentCode = trim((string)($data['ma_sv'] ?? ($data['student_code'] ?? '')));
        if ($studentId <= 0 && $studentCode !== '') {
            $found = $this->findStudentByCode($studentCode);
            if ($found) {
                $studentId = (int)$found['ID'];
            }
        }

        $typeId = (int)($data['type_id'] ?? 0);
        $date = trim((string)($data['date'] ?? ''));
        $time = trim((string)($data['time'] ?? ''));
        $datetimeInput = trim((string)($data['datetime'] ?? ($data['thoi_diem'] ?? '')));
        if ($datetimeInput !== '') {
            $parsed = $this->parseViolationDateTime($datetimeInput);
            if ($parsed) {
                if ($date === '') $date = $parsed['date'];
                if ($time === '') $time = $parsed['time'];
            }
        }
        if ($date === '') {
            $date = date('Y-m-d');
        }
        if ($time === '') {
            $time = date('H:i');
        }

        $areaId = !empty($data['area_id']) ? (int)$data['area_id'] : null;
        $room = !empty($data['room']) ? trim((string)$data['room']) : (!empty($data['vi_tri']) ? trim((string)$data['vi_tri']) : null);
        $note = !empty($data['note']) ? trim((string)$data['note']) : null;
        $confirmDuplicate = ($data['confirm_duplicate'] ?? false) === true;

        if ($areaId === null && $room !== null && $room !== '') {
            $areaId = $this->matchAreaIdByName($room);
        }

        if ($studentId <= 0) {
            throw new InvalidArgumentException('Không tìm thấy sinh viên tương ứng để ghi nhận vi phạm.');
        }
        if ($typeId <= 0) {
            throw new InvalidArgumentException('Vui lòng chọn loại vi phạm.');
        }

        $datetimeStr = $date . ' ' . (strlen($time) === 5 ? $time . ':00' : $time);

        $same = $pdo->prepare(
            'SELECT COUNT(*) FROM CT_VIPHAM WITH (UPDLOCK, HOLDLOCK)
             WHERE ID_SV = :student AND ID_LOI = :type AND NGAY_VIPHAM = :day'
        );
        $same->execute(['student' => $studentId, 'type' => $typeId, 'day' => $date]);
        if ((int)$same->fetchColumn() > 0 && !$confirmDuplicate) {
            return [
                'duplicate' => true,
                'message' => 'Sinh viên đã có vi phạm cùng loại trong ngày này. Xác nhận nếu đây là một lần vi phạm khác do Đội Cờ Đỏ ghi nhận.',
                'code' => 'duplicate_same_day',
            ];
        }

        $maHk = !empty($data['ma_hk']) ? trim((string)$data['ma_hk']) : $this->resolveSemesterCodeByDate($pdo, $date);

        $insert = $pdo->prepare(
            'INSERT INTO CT_VIPHAM (ID_LOI, ID_SV, NGAY_VIPHAM, THOI_DIEM, KHU_VUC_ID, GHI_CHU, MA_HK) 
             OUTPUT INSERTED.ID
             VALUES (:type, :student, :day, :thoidiem, :area, :note, :ma_hk)'
        );
        $insert->bindValue(':type', $typeId, PDO::PARAM_INT);
        $insert->bindValue(':student', $studentId, PDO::PARAM_INT);
        $insert->bindValue(':day', $date);
        $insert->bindValue(':thoidiem', $datetimeStr);
        $insert->bindValue(':area', $areaId, $areaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $insert->bindValue(':note', $note, $note === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $insert->bindValue(':ma_hk', $maHk, $maHk === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $insert->execute();

        return ['id' => (int)$insert->fetchColumn(), 'student_id' => $studentId];
    }

    public function deleteViolation(int $violationId, int $studentId): void
    {
        $pdo = $this->getPdo();
        $delete = $pdo->prepare('DELETE FROM CT_VIPHAM WHERE ID = :id AND ID_SV = :student');
        $delete->execute(['id' => $violationId, 'student' => $studentId]);
        if ($delete->rowCount() === 0) {
            throw new InvalidArgumentException('Không tìm thấy bản ghi vi phạm của sinh viên này.');
        }
    }

    // ---------------------------------------------------------------- Excel Import & Export
    public function importColumns(): array
    {
        return [
            'KHOA' => ['MA_KHOA', 'TEN_KHOA'],
            'NGANH' => ['MA_NGANH', 'TEN_NGANH', 'TEN_KHOA'],
            'LOP' => ['MA_LOP', 'TEN_NGANH'],
            'SINHVIEN' => ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'],
            'VIPHAM' => ['MA_SV', 'HOTEN', 'VI_TRI', 'THOI_DIEM', 'TEN_LOI'],
        ];
    }

    public function columnLabels(): array
    {
        return [
            'STT' => 'STT',
            'MA_SV' => 'Mã sinh viên',
            'HOTEN' => 'Họ và tên',
            'NIEN_KHOA' => 'Niên khóa',
            'MA_LOP' => 'Mã lớp',
            'MA_NGANH' => 'Mã ngành',
            'TEN_NGANH' => 'Tên ngành',
            'MA_KHOA' => 'Mã khoa',
            'TEN_KHOA' => 'Tên khoa',
            'SO_VI_PHAM' => 'Số lượt vi phạm',
            'VI_TRI' => 'Vị trí bị ghi nhận',
            'THOI_DIEM' => 'Thời gian bị ghi nhận',
            'TEN_LOI' => 'Lỗi bị ghi nhận',
        ];
    }

    public function importHeaderLabels(string $dataset): array
    {
        $cols = $this->importColumns()[strtoupper($dataset)] ?? ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'];
        $labels = $this->columnLabels();
        return array_map(fn(string $c) => $labels[$c] ?? $c, $cols);
    }

    public function sampleRows(string $dataset): array
    {
        return match (strtoupper($dataset)) {
            'KHOA' => [
                ['CNTT', 'Khoa Công nghệ thông tin'],
                ['KT', 'Khoa Kinh tế'],
            ],
            'NGANH' => [
                ['KTPM', 'Kỹ thuật phần mềm', 'Khoa Công nghệ thông tin'],
                ['HTTT', 'Hệ thống thông tin', 'Khoa Công nghệ thông tin'],
            ],
            'LOP' => [
                ['14DHTH01', 'Kỹ thuật phần mềm'],
                ['14DHTH02', 'Hệ thống thông tin'],
            ],
            'VIPHAM' => [
                ['21110823', 'Nguyễn Văn An', 'Phòng A201', '2026-10-21 07:30', 'Vi phạm sai đồng phục'],
                ['21110824', 'Trần Thị Bình', 'Cổng chính', '2026-10-21 07:45', 'Thiếu thẻ sinh viên'],
            ],
            default => [
                ['21110823', 'Nguyễn Văn An', '14', '14DHTH01'],
                ['21110824', 'Trần Thị Bình', '14', '14DHTH02'],
            ],
        };
    }

    public function verifyExportPassword(string $password): bool
    {
        $expected = (string)config('cim.export_password');
        if ($expected === '') {
            return false;
        }
        if (str_starts_with($expected, '$2y$') || str_starts_with($expected, '$2a$')) {
            return password_verify($password, $expected);
        }
        return hash_equals($expected, $password);
    }

    public function stripAccents(string $str): string
    {
        $str = preg_replace('/[áàảãạăắằẳẵặâấầẩẫậ]/u', 'a', $str);
        $str = preg_replace('/[ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ]/u', 'A', $str);
        $str = preg_replace('/[éèẻẽẹêếềểễệ]/u', 'e', $str);
        $str = preg_replace('/[ÉÈẺẼẸÊẾỀỂỄỆ]/u', 'E', $str);
        $str = preg_replace('/[íìỉĩị]/u', 'i', $str);
        $str = preg_replace('/[ÍÌỈĨỊ]/u', 'I', $str);
        $str = preg_replace('/[óòỏõọôốồổỗộơớờởỡợ]/u', 'o', $str);
        $str = preg_replace('/[ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ]/u', 'O', $str);
        $str = preg_replace('/[úùủũụưứừửữự]/u', 'u', $str);
        $str = preg_replace('/[ÚÙỦŨỤƯỨỪỬỮỰ]/u', 'U', $str);
        $str = preg_replace('/[ýỳỷỹỵ]/u', 'y', $str);
        $str = preg_replace('/[ÝỲỶỸỴ]/u', 'Y', $str);
        $str = preg_replace('/[đ]/u', 'd', $str);
        $str = preg_replace('/[Đ]/u', 'D', $str);
        return (string)$str;
    }

    public function normalizeHeaderAlias(string $rawHeader, string $dataset): string
    {
        $dataset = strtoupper($dataset);
        $clean = strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $this->stripAccents($rawHeader)));

        if ($dataset === 'KHOA') {
            return match ($clean) {
                'MAKHOA', 'KHOA', 'MA', 'FACULTYCODE', 'CODE', 'MA_KHOA' => 'MA_KHOA',
                'TENKHOA', 'TEN', 'KHOAVIEN', 'FACULTYNAME', 'NAME', 'TEN_KHOA' => 'TEN_KHOA',
                default => $rawHeader,
            };
        }

        if ($dataset === 'NGANH') {
            return match ($clean) {
                'MANGANH', 'NGANH', 'MA', 'MAJORCODE', 'CODE', 'MA_NGANH' => 'MA_NGANH',
                'TENNGANH', 'TEN', 'NGANHHOC', 'MAJORNAME', 'NAME', 'TEN_NGANH' => 'TEN_NGANH',
                'TENKHOA', 'KHOA', 'THUOCKHOA', 'KHOAVIEN', 'FACULTYNAME', 'MAKHOA', 'FACULTYCODE', 'MA_KHOA', 'TEN_KHOA' => 'TEN_KHOA',
                default => $rawHeader,
            };
        }

        if ($dataset === 'LOP') {
            return match ($clean) {
                'MALOP', 'LOP', 'MA', 'CLASS', 'CLASSCODE', 'LOPHOC', 'LOPSINHHOAT', 'MA_LOP' => 'MA_LOP',
                'TENNGANH', 'NGANH', 'THUOCNGANH', 'MAJORNAME', 'NGANHHOC', 'TEN', 'MANGANH', 'MAJORCODE', 'MA_NGANH', 'TEN_NGANH' => 'TEN_NGANH',
                default => $rawHeader,
            };
        }

        if ($dataset === 'VIPHAM') {
            return match ($clean) {
                'MASV', 'MSSV', 'MASINHVIEN', 'MASOSINHVIEN', 'MASINHVIENMSSV', 'STUDENTID', 'STUDENTCODE', 'CODE', 'MA', 'MA_SV' => 'MA_SV',
                'HOTEN', 'HOVATEN', 'TENSINHVIEN', 'TENSV', 'HOVATENSINHVIEN', 'FULLNAME', 'NAME', 'HOTEN' => 'HOTEN',
                'VITRI', 'VITRIBIGHINHAN', 'PHONG', 'PHONGHOC', 'DIADIEM', 'KHUVUC', 'LOCATION', 'ROOM', 'PLACE', 'VI_TRI' => 'VI_TRI',
                'THOIDIEM', 'THOIGIAN', 'THOIGIANBIGHINHAN', 'THOIDIEMBIGHINHAN', 'NGAYGIO', 'NGAYTHANG', 'TIME', 'DATETIME', 'DATE', 'THOI_DIEM' => 'THOI_DIEM',
                'TENLOI', 'LOI', 'LOIVIPHAM', 'LOIBIGHINHAN', 'HINHTHUCVIPHAM', 'VIOLATION', 'VIOLATIONTYPE', 'TYPE', 'TEN_LOI' => 'TEN_LOI',
                default => $rawHeader,
            };
        }

        // SINHVIEN
        return match ($clean) {
            'MASV', 'MSSV', 'MASINHVIEN', 'MASOSINHVIEN', 'MASINHVIENMSSV', 'MASOSINHVIENMSSV', 'STUDENTID', 'STUDENTCODE', 'CODE', 'MA', 'MA_SV' => 'MA_SV',
            'HOTEN', 'HOVATEN', 'TENSINHVIEN', 'TENSV', 'HOVATENSINHVIEN', 'FULLNAME', 'NAME', 'HOTEN' => 'HOTEN',
            'NIENKHOA', 'KHOAHOC', 'KHOADAOTAO', 'KHOA', 'NAMNHAPHOC', 'ACADEMICYEAR', 'BATCH', 'NIEN_KHOA' => 'NIEN_KHOA',
            'MALOP', 'LOP', 'LOPHOC', 'LOPSINHHOAT', 'CLASS', 'CLASSCODE', 'MA_LOP' => 'MA_LOP',
            default => $rawHeader,
        };
    }

    public function parseXlsxRows(string $filePath): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Máy chủ chưa bật PHP ZipArchive extension.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Không thể mở tệp tin Excel (.xlsx).');
        }

        $strings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $sharedXml = @simplexml_load_string(
                (string)$zip->getFromName('xl/sharedStrings.xml'),
                SimpleXMLElement::class,
                LIBXML_NONET | LIBXML_NOCDATA
            );
            if ($sharedXml) {
                foreach ($sharedXml->si as $si) {
                    if (isset($si->t)) {
                        $strings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $str = '';
                        foreach ($si->r as $r) {
                            $str .= (string)($r->t ?? '');
                        }
                        $strings[] = $str;
                    } else {
                        $strings[] = '';
                    }
                }
            }
        }

        $sheetName = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetName) === false) {
            $zip->close();
            throw new RuntimeException('Không tìm thấy trang tính (Sheet1) trong tệp tin Excel.');
        }

        $sheetXml = @simplexml_load_string(
            (string)$zip->getFromName($sheetName),
            SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOCDATA
        );
        $zip->close();

        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                preg_match('/^([A-Z]+)(\d+)$/', $ref, $matches);
                $colLetters = $matches[1] ?? 'A';
                $colIdx = 0;
                for ($i = 0; $i < strlen($colLetters); $i++) {
                    $colIdx = $colIdx * 26 + (ord($colLetters[$i]) - 64);
                }
                $colIdx -= 1;

                $type = (string)($c['t'] ?? '');
                $val = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $val = $strings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)($c->is->t ?? '');
                } else {
                    $val = (string)($c->v ?? '');
                }

                $rowData[$colIdx] = trim((string)$val);
            }

            if (!empty($rowData)) {
                $maxCol = max(array_keys($rowData));
                $fullRow = [];
                $hasContent = false;
                for ($i = 0; $i <= $maxCol; $i++) {
                    $cellVal = $rowData[$i] ?? '';
                    $fullRow[$i] = $cellVal;
                    if ($cellVal !== '') {
                        $hasContent = true;
                    }
                }
                if ($hasContent) {
                    $rows[] = $fullRow;
                }
            }
        }

        return $rows;
    }

    public function detectHeaderRow(array $rawRows, string $dataset): array
    {
        $expected = $this->importColumns()[$dataset] ?? [];
        $bestRowIndex = -1;
        $bestHeaderMap = [];
        $maxMatches = 0;

        $checkLimit = min(10, count($rawRows));
        for ($r = 0; $r < $checkLimit; $r++) {
            $row = $rawRows[$r];
            $currentMap = [];
            $matchCount = 0;

            foreach ($row as $colIdx => $cellText) {
                $trimmed = trim((string)$cellText);
                if ($trimmed === '') {
                    continue;
                }
                $canonical = $this->normalizeHeaderAlias($trimmed, $dataset);
                if (in_array($canonical, $expected, true) && !isset($currentMap[$canonical])) {
                    $currentMap[$canonical] = $colIdx;
                    $matchCount++;
                }
            }

            if ($matchCount > $maxMatches) {
                $maxMatches = $matchCount;
                $bestRowIndex = $r;
                $bestHeaderMap = $currentMap;
            }
        }

        return [
            'header_row_index' => $bestRowIndex,
            'header_map' => $bestHeaderMap,
            'matched_count' => $maxMatches,
        ];
    }

    public function normalizeRecordValues(string $dataset, array $values, bool &$wasNormalized): array
    {
        $wasNormalized = false;
        $cleaned = [];

        foreach ($values as $col => $val) {
            $orig = (string)$val;
            // Xóa ký tự vô hình / zero-width space / non-breaking space
            $trimmed = trim(str_replace(["\xE2\x80\x8B", "\xC2\xA0"], ['', ' '], $orig));
            // Gộp nhiều dấu cách thành 1
            $trimmed = (string)preg_replace('/\s+/', ' ', $trimmed);

            if (in_array($col, ['MA_SV', 'MA_LOP', 'MA_NGANH', 'MA_KHOA'], true)) {
                $codeVal = strtoupper(str_replace(' ', '', $trimmed));
                if ($codeVal !== $orig) {
                    $wasNormalized = true;
                }
                $cleaned[$col] = $codeVal;
            } elseif ($col === 'HOTEN') {
                if ($trimmed !== '' && (mb_strtoupper($trimmed, 'UTF-8') === $trimmed || mb_strtolower($trimmed, 'UTF-8') === $trimmed)) {
                    $nameVal = mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
                    $wasNormalized = true;
                    $cleaned[$col] = $nameVal;
                } else {
                    if ($trimmed !== $orig) {
                        $wasNormalized = true;
                    }
                    $cleaned[$col] = $trimmed;
                }
            } elseif ($col === 'NIEN_KHOA') {
                if (preg_match('/\d+/', $trimmed, $m)) {
                    $yearVal = $m[0];
                    if ($yearVal !== $orig) {
                        $wasNormalized = true;
                    }
                    $cleaned[$col] = $yearVal;
                } else {
                    $cleaned[$col] = $trimmed;
                }
            } else {
                if ($trimmed !== $orig) {
                    $wasNormalized = true;
                }
                $cleaned[$col] = $trimmed;
            }
        }

        return $cleaned;
    }

    public function previewImport(string $dataset, mixed $file): array
    {
        $dataset = strtoupper($dataset);
        $expectedCols = $this->importColumns()[$dataset] ?? null;
        if (!$expectedCols) {
            throw new InvalidArgumentException('Loại danh mục import không hợp lệ.');
        }

        if (!$file || !is_object($file) || !method_exists($file, 'isValid') || !$file->isValid()) {
            throw new InvalidArgumentException('Tệp tin tải lên không hợp lệ.');
        }

        if ($file->getSize() > self::IMPORT_MAX_BYTES) {
            throw new InvalidArgumentException('Tệp tin vượt quá dung lượng tối đa cho phép (15MB).');
        }

        $filePath = $file->getRealPath();
        $rawRows = $this->parseXlsxRows($filePath);

        if (empty($rawRows)) {
            throw new InvalidArgumentException('Tệp tin Excel không có dữ liệu.');
        }

        $detected = $this->detectHeaderRow($rawRows, $dataset);
        $headerRowIdx = $detected['header_row_index'];
        $headerMap = $detected['header_map'];

        $missingCols = array_diff($expectedCols, array_keys($headerMap));
        if (!empty($missingCols)) {
            $labels = $this->columnLabels();
            $missingFriendly = array_map(fn($col) => ($labels[$col] ?? $col) . " ({$col})", $missingCols);
            $foundFriendly = array_map(fn($col) => ($labels[$col] ?? $col) . " ({$col})", array_keys($headerMap));
            throw new InvalidArgumentException(
                'Không tìm thấy các cột bắt buộc: ' . implode(', ', $missingFriendly) .
                '. Các cột nhận diện được: ' . (empty($headerMap) ? '(không có)' : implode(', ', $foundFriendly))
            );
        }

        $pdo = $this->getPdo();

        // Nạp trước dữ liệu quan hệ và mã hiện có trong CSDL để đối soát nhanh
        $existingCodes = [];
        $facultyLookup = [];
        $majorLookup = [];
        $classLookup = [];

        if ($dataset === 'KHOA') {
            $existingCodes = $pdo->query('SELECT MA_KHOA FROM KHOA')->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($dataset === 'NGANH') {
            $existingCodes = $pdo->query('SELECT MA_NGANH FROM NGANH')->fetchAll(PDO::FETCH_COLUMN);
            $faculties = $pdo->query('SELECT ID, MA_KHOA, TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX FROM KHOA')->fetchAll();
            foreach ($faculties as $f) {
                $name = $this->decodeHexUnicode($f['TEN_KHOA_HEX'] ?? null, $f['TEN_KHOA']);
                $code = strtoupper(trim((string)$f['MA_KHOA']));
                $id = (int)$f['ID'];
                $item = ['id' => $id, 'code' => $code, 'name' => $name];
                $normName = mb_strtolower(trim($name), 'UTF-8');
                if ($normName !== '') {
                    $facultyLookup[$normName] = $item;
                }
                if ($code !== '') {
                    $facultyLookup[$code] = $item;
                }
            }
        } elseif ($dataset === 'LOP') {
            $existingCodes = $pdo->query('SELECT MA_LOP FROM LOP')->fetchAll(PDO::FETCH_COLUMN);
            $majors = $pdo->query('SELECT ID, MA_NGANH, TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX FROM NGANH')->fetchAll();
            foreach ($majors as $m) {
                $name = $this->decodeHexUnicode($m['TEN_NGANH_HEX'] ?? null, $m['TEN_NGANH']);
                $code = strtoupper(trim((string)$m['MA_NGANH']));
                $id = (int)$m['ID'];
                $item = ['id' => $id, 'code' => $code, 'name' => $name];
                $normName = mb_strtolower(trim($name), 'UTF-8');
                if ($normName !== '') {
                    $majorLookup[$normName] = $item;
                }
                if ($code !== '') {
                    $majorLookup[$code] = $item;
                }
            }
        } elseif ($dataset === 'SINHVIEN') {
            $existingCodes = $pdo->query('SELECT MA_SV FROM SINHVIEN')->fetchAll(PDO::FETCH_COLUMN);
            $classCodes = $pdo->query('SELECT MA_LOP FROM LOP')->fetchAll(PDO::FETCH_COLUMN);
            $classLookup = array_fill_keys(array_map('strtoupper', $classCodes), true);
        } elseif ($dataset === 'VIPHAM') {
            $students = $pdo->query('SELECT ID, MA_SV, HOTEN, CONVERT(VARCHAR(MAX), CAST(HOTEN AS VARBINARY(MAX)), 2) AS HOTEN_HEX FROM SINHVIEN')->fetchAll();
            $studentLookup = [];
            foreach ($students as $s) {
                $sCode = strtoupper(trim((string)$s['MA_SV']));
                $studentLookup[$sCode] = [
                    'id' => (int)$s['ID'],
                    'name' => $this->decodeHexUnicode($s['HOTEN_HEX'] ?? null, $s['HOTEN']),
                ];
            }

            $types = $pdo->query('SELECT ID, MA_LOI, TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX FROM LOI_VIPHAM WHERE DANG_SU_DUNG = 1')->fetchAll();
            $typeLookup = [];
            foreach ($types as $t) {
                $tName = $this->decodeHexUnicode($t['TEN_LOI_HEX'] ?? null, $t['TEN_LOI']);
                $tCode = strtoupper(trim((string)($t['MA_LOI'] ?? '')));
                $tItem = ['id' => (int)$t['ID'], 'name' => $tName, 'code' => $tCode];
                $typeLookup[mb_strtolower($tName, 'UTF-8')] = $tItem;
                if ($tCode !== '') {
                    $typeLookup[$tCode] = $tItem;
                }
            }

            $areas = $pdo->query('SELECT ID, MA_KHU_VUC, TEN_KHU_VUC, CONVERT(VARCHAR(MAX), CAST(TEN_KHU_VUC AS VARBINARY(MAX)), 2) AS TEN_KHU_VUC_HEX FROM KHU_VUC WHERE DANG_SU_DUNG = 1')->fetchAll();
            $areaLookup = [];
            foreach ($areas as $a) {
                $aName = $this->decodeHexUnicode($a['TEN_KHU_VUC_HEX'] ?? null, $a['TEN_KHU_VUC']);
                $aCode = strtoupper(trim((string)$a['MA_KHU_VUC']));
                $areaLookup[mb_strtolower($aName, 'UTF-8')] = (int)$a['ID'];
                if ($aCode !== '') {
                    $areaLookup[$aCode] = (int)$a['ID'];
                }
            }
        }

        $existingCodesMap = array_fill_keys(array_map('strtoupper', $existingCodes), true);

        $previewRows = [];
        $counts = ['valid' => 0, 'duplicate' => 0, 'error' => 0, 'normalized' => 0];
        $seenInFile = [];

        $totalDataRows = count($rawRows) - ($headerRowIdx + 1);
        if ($totalDataRows > self::IMPORT_MAX_ROWS) {
            throw new InvalidArgumentException('Số lượng dòng dữ liệu (' . $totalDataRows . ') vượt quá giới hạn tối đa (' . self::IMPORT_MAX_ROWS . ' dòng).');
        }

        for ($r = $headerRowIdx + 1; $r < count($rawRows); $r++) {
            $rawRow = $rawRows[$r];
            $rowNumber = $r + 1;

            $values = [];
            foreach ($expectedCols as $col) {
                $colIdx = $headerMap[$col];
                $values[$col] = $rawRow[$colIdx] ?? '';
            }

            $wasNormalized = false;
            $cleanValues = $this->normalizeRecordValues($dataset, $values, $wasNormalized);
            if ($wasNormalized) {
                $counts['normalized']++;
            }

            $rowStatus = 'valid';
            $errors = [];

            // Kiểm tra trường bắt buộc và định dạng
            $primaryCode = match ($dataset) {
                'KHOA' => $cleanValues['MA_KHOA'] ?? '',
                'NGANH' => $cleanValues['MA_NGANH'] ?? '',
                'LOP' => $cleanValues['MA_LOP'] ?? '',
                default => $cleanValues['MA_SV'] ?? '',
            };

            if ($primaryCode === '') {
                $rowStatus = 'error';
                $errors[] = 'Mã định danh không được để trống';
            } elseif (preg_match('/^[A-Za-z0-9_-]+$/', $primaryCode) !== 1) {
                $rowStatus = 'error';
                $errors[] = 'Mã chỉ được chứa chữ cái, số, dấu _ hoặc -';
            }

            // Kiểm tra các trường khác theo từng dataset
            if ($dataset === 'KHOA') {
                if (empty($cleanValues['TEN_KHOA'])) {
                    $rowStatus = 'error';
                    $errors[] = 'Tên khoa không được để trống';
                }
            } elseif ($dataset === 'NGANH') {
                if (empty($cleanValues['TEN_NGANH'])) {
                    $rowStatus = 'error';
                    $errors[] = 'Tên ngành không được để trống';
                }
                $parent = trim((string)($cleanValues['TEN_KHOA'] ?? ''));
                $normParent = mb_strtolower($parent, 'UTF-8');
                $upperParent = strtoupper(str_replace(' ', '', $parent));
                $foundFaculty = $facultyLookup[$normParent] ?? ($facultyLookup[$upperParent] ?? null);
                if ($parent === '' || !$foundFaculty) {
                    $rowStatus = 'error';
                    $errors[] = $parent === '' ? 'Tên khoa không được để trống' : "Khoa '{$parent}' chưa tồn tại trong CSDL";
                } else {
                    $cleanValues['TEN_KHOA'] = $foundFaculty['name'];
                }
            } elseif ($dataset === 'LOP') {
                $parent = trim((string)($cleanValues['TEN_NGANH'] ?? ''));
                $normParent = mb_strtolower($parent, 'UTF-8');
                $upperParent = strtoupper(str_replace(' ', '', $parent));
                $foundMajor = $majorLookup[$normParent] ?? ($majorLookup[$upperParent] ?? null);
                if ($parent === '' || !$foundMajor) {
                    $rowStatus = 'error';
                    $errors[] = $parent === '' ? 'Tên ngành không được để trống' : "Ngành '{$parent}' chưa tồn tại trong CSDL";
                } else {
                    $cleanValues['TEN_NGANH'] = $foundMajor['name'];
                }
            } elseif ($dataset === 'SINHVIEN') {
                if (empty($cleanValues['HOTEN'])) {
                    $rowStatus = 'error';
                    $errors[] = 'Họ và tên không được để trống';
                }
                $parent = $cleanValues['MA_LOP'] ?? '';
                if ($parent === '' || !isset($classLookup[$parent])) {
                    $rowStatus = 'error';
                    $errors[] = "Mã lớp '{$parent}' chưa tồn tại trong CSDL";
                }
                if (!empty($cleanValues['NIEN_KHOA']) && !ctype_digit((string)$cleanValues['NIEN_KHOA'])) {
                    $rowStatus = 'error';
                    $errors[] = 'Niên khóa phải là chữ số';
                }
            } elseif ($dataset === 'VIPHAM') {
                if (!isset($studentLookup[$primaryCode])) {
                    $rowStatus = 'error';
                    $errors[] = "Mã sinh viên '{$primaryCode}' không tồn tại trong hệ thống";
                } else {
                    $foundName = $studentLookup[$primaryCode]['name'];
                    if (empty($cleanValues['HOTEN']) || mb_strtolower($cleanValues['HOTEN'], 'UTF-8') !== mb_strtolower($foundName, 'UTF-8')) {
                        $cleanValues['HOTEN'] = $foundName;
                        $wasNormalized = true;
                    }
                }

                $rawLoi = trim((string)($cleanValues['TEN_LOI'] ?? ''));
                $normLoi = mb_strtolower($rawLoi, 'UTF-8');
                $matchedType = $typeLookup[$normLoi] ?? ($typeLookup[strtoupper($rawLoi)] ?? null);
                if (!$matchedType) {
                    foreach ($typeLookup as $key => $item) {
                        if ($key !== '' && (str_contains($normLoi, $key) || str_contains($key, $normLoi))) {
                            $matchedType = $item;
                            break;
                        }
                    }
                }
                if ($matchedType) {
                    $cleanValues['TEN_LOI'] = $matchedType['name'];
                    $cleanValues['_type_id'] = $matchedType['id'];
                } else {
                    $rowStatus = 'error';
                    $errors[] = "Loại lỗi '{$rawLoi}' không hợp lệ hoặc chưa có trong danh mục lỗi";
                }

                $rawTime = trim((string)($cleanValues['THOI_DIEM'] ?? ''));
                $parsedTime = $this->parseViolationDateTime($rawTime);
                if ($parsedTime) {
                    $cleanValues['THOI_DIEM'] = $parsedTime['datetime'];
                    $cleanValues['_ngay'] = $parsedTime['date'];
                    $cleanValues['_gio'] = $parsedTime['time'];
                } else {
                    $rowStatus = 'error';
                    $errors[] = "Thời gian '{$rawTime}' không đúng định dạng (VD: 2026-10-21 07:30 hoặc 21/10/2026 07:30)";
                }

                $rawPlace = trim((string)($cleanValues['VI_TRI'] ?? ''));
                if ($rawPlace === '') {
                    $cleanValues['VI_TRI'] = 'Khu vực chung';
                }
                $matchedAreaId = null;
                $cleanPlaceLower = mb_strtolower($cleanValues['VI_TRI'], 'UTF-8');
                foreach ($areaLookup as $areaNameLower => $aId) {
                    if (str_contains($cleanPlaceLower, $areaNameLower) || str_contains($areaNameLower, $cleanPlaceLower)) {
                        $matchedAreaId = $aId;
                        break;
                    }
                }
                $cleanValues['_area_id'] = $matchedAreaId;
            }

            // Kiểm tra trùng lặp (Duplicate)
            if ($rowStatus !== 'error') {
                if ($dataset === 'VIPHAM') {
                    $dupKey = $primaryCode . '|' . ($cleanValues['TEN_LOI'] ?? '') . '|' . ($cleanValues['THOI_DIEM'] ?? '');
                    if (isset($seenInFile[$dupKey])) {
                        $rowStatus = 'duplicate';
                        $errors[] = "Bản ghi vi phạm trùng lặp với dòng " . $seenInFile[$dupKey] . " trong file";
                    } else {
                        $seenInFile[$dupKey] = $rowNumber;
                    }
                } else {
                    if (isset($seenInFile[$primaryCode])) {
                        $rowStatus = 'duplicate';
                        $errors[] = "Mã '{$primaryCode}' bị trùng với dòng " . $seenInFile[$primaryCode] . ' trong file';
                    } elseif (isset($existingCodesMap[$primaryCode])) {
                        $rowStatus = 'duplicate';
                        $errors[] = "Mã '{$primaryCode}' đã tồn tại trong CSDL";
                    } else {
                        $seenInFile[$primaryCode] = $rowNumber;
                    }
                }
            }

            $counts[$rowStatus]++;
            $previewRows[] = [
                'row' => $rowNumber,
                'values' => $cleanValues,
                'status' => $rowStatus,
                'errors' => $errors,
            ];
        }

        $previewToken = bin2hex(random_bytes(16));
        $cacheDir = storage_path('app/import_previews');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // Dọn dẹp các preview cũ quá hạn
        foreach (glob($cacheDir . '/*.json') ?: [] as $oldFile) {
            if (filemtime($oldFile) < time() - self::PREVIEW_TTL) {
                @unlink($oldFile);
            }
        }

        $payload = [
            'dataset' => $dataset,
            'counts' => $counts,
            'can_commit' => $counts['error'] === 0 && $counts['valid'] > 0,
            'rows' => $previewRows,
            'created_at' => time(),
        ];

        file_put_contents($cacheDir . '/' . $previewToken . '.json', json_encode($payload, JSON_UNESCAPED_UNICODE));

        return [
            'dataset' => $dataset,
            'preview_token' => $previewToken,
            'counts' => $counts,
            'can_commit' => $payload['can_commit'],
            'rows' => $previewRows,
        ];
    }

    public function commitImport(string $previewToken): array
    {
        $cacheFile = storage_path('app/import_previews/' . preg_replace('/[^a-zA-Z0-9]/', '', $previewToken) . '.json');
        if (!file_exists($cacheFile)) {
            throw new RuntimeException('Phiên xem trước dữ liệu không tồn tại hoặc đã hết hạn. Vui lòng tải lại tệp tin.');
        }

        $data = json_decode((string)file_get_contents($cacheFile), true);
        if (!$data || !is_array($data)) {
            throw new RuntimeException('Dữ liệu xem trước bị lỗi. Vui lòng tải lại tệp tin.');
        }

        if (($data['counts']['error'] ?? 1) > 0) {
            throw new RuntimeException('Tệp tin còn chứa dòng lỗi. Vui lòng sửa lỗi trước khi nhập.');
        }

        $dataset = strtoupper((string)($data['dataset'] ?? ''));
        $rows = $data['rows'] ?? [];

        $pdo = $this->getPdo();
        $pdo->beginTransaction();

        $inserted = 0;
        $skipped = 0;

        try {
            if ($dataset === 'KHOA') {
                $check = $pdo->prepare('SELECT COUNT(*) FROM KHOA WHERE MA_KHOA = ?');
                $insert = $pdo->prepare('INSERT INTO KHOA (MA_KHOA, TEN_KHOA) VALUES (?, ?)');
                foreach ($rows as $item) {
                    if ($item['status'] !== 'valid') {
                        $skipped++;
                        continue;
                    }
                    $check->execute([$item['values']['MA_KHOA']]);
                    if ((int)$check->fetchColumn() > 0) {
                        $skipped++;
                        continue;
                    }
                    $insert->execute([$item['values']['MA_KHOA'], $item['values']['TEN_KHOA']]);
                    $inserted++;
                }
            } elseif ($dataset === 'NGANH') {
                $faculties = $pdo->query('SELECT ID, MA_KHOA, TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX FROM KHOA')->fetchAll();
                $facultyLookup = [];
                foreach ($faculties as $f) {
                    $name = $this->decodeHexUnicode($f['TEN_KHOA_HEX'] ?? null, $f['TEN_KHOA']);
                    $code = strtoupper(trim((string)$f['MA_KHOA']));
                    $id = (int)$f['ID'];
                    $normName = mb_strtolower(trim($name), 'UTF-8');
                    if ($normName !== '') {
                        $facultyLookup[$normName] = $id;
                    }
                    if ($code !== '') {
                        $facultyLookup[$code] = $id;
                    }
                }
                $check = $pdo->prepare('SELECT COUNT(*) FROM NGANH WHERE MA_NGANH = ?');
                $insert = $pdo->prepare('INSERT INTO NGANH (MA_NGANH, TEN_NGANH, KHOA_ID) VALUES (?, ?, ?)');
                foreach ($rows as $item) {
                    if ($item['status'] !== 'valid') {
                        $skipped++;
                        continue;
                    }
                    $check->execute([$item['values']['MA_NGANH']]);
                    if ((int)$check->fetchColumn() > 0) {
                        $skipped++;
                        continue;
                    }
                    $parent = trim((string)($item['values']['TEN_KHOA'] ?? ($item['values']['MA_KHOA'] ?? '')));
                    $normParent = mb_strtolower($parent, 'UTF-8');
                    $upperParent = strtoupper(str_replace(' ', '', $parent));
                    $khoaId = $facultyLookup[$normParent] ?? ($facultyLookup[$upperParent] ?? null);
                    if (!$khoaId) {
                        throw new RuntimeException("Khoa '{$parent}' không tồn tại.");
                    }
                    $insert->execute([$item['values']['MA_NGANH'], $item['values']['TEN_NGANH'], (int)$khoaId]);
                    $inserted++;
                }
            } elseif ($dataset === 'LOP') {
                $majors = $pdo->query('SELECT ID, MA_NGANH, TEN_NGANH, CONVERT(VARCHAR(MAX), CAST(TEN_NGANH AS VARBINARY(MAX)), 2) AS TEN_NGANH_HEX FROM NGANH')->fetchAll();
                $majorLookup = [];
                foreach ($majors as $m) {
                    $name = $this->decodeHexUnicode($m['TEN_NGANH_HEX'] ?? null, $m['TEN_NGANH']);
                    $code = strtoupper(trim((string)$m['MA_NGANH']));
                    $id = (int)$m['ID'];
                    $normName = mb_strtolower(trim($name), 'UTF-8');
                    if ($normName !== '') {
                        $majorLookup[$normName] = $id;
                    }
                    if ($code !== '') {
                        $majorLookup[$code] = $id;
                    }
                }
                $check = $pdo->prepare('SELECT COUNT(*) FROM LOP WHERE MA_LOP = ?');
                $insert = $pdo->prepare('INSERT INTO LOP (MA_LOP, NGANH_ID) VALUES (?, ?)');
                foreach ($rows as $item) {
                    if ($item['status'] !== 'valid') {
                        $skipped++;
                        continue;
                    }
                    $check->execute([$item['values']['MA_LOP']]);
                    if ((int)$check->fetchColumn() > 0) {
                        $skipped++;
                        continue;
                    }
                    $parent = trim((string)($item['values']['TEN_NGANH'] ?? ($item['values']['MA_NGANH'] ?? '')));
                    $normParent = mb_strtolower($parent, 'UTF-8');
                    $upperParent = strtoupper(str_replace(' ', '', $parent));
                    $nganhId = $majorLookup[$normParent] ?? ($majorLookup[$upperParent] ?? null);
                    if (!$nganhId) {
                        throw new RuntimeException("Ngành '{$parent}' không tồn tại.");
                    }
                    $insert->execute([$item['values']['MA_LOP'], (int)$nganhId]);
                    $inserted++;
                }
            } elseif ($dataset === 'SINHVIEN') {
                $check = $pdo->prepare('SELECT COUNT(*) FROM SINHVIEN WHERE MA_SV = ?');
                $getParent = $pdo->prepare('SELECT ID FROM LOP WHERE MA_LOP = ?');
                $insert = $pdo->prepare('INSERT INTO SINHVIEN (MA_SV, HOTEN, NIEN_KHOA, LOP_ID) VALUES (?, ?, ?, ?)');
                foreach ($rows as $item) {
                    if ($item['status'] !== 'valid') {
                        $skipped++;
                        continue;
                    }
                    $check->execute([$item['values']['MA_SV']]);
                    if ((int)$check->fetchColumn() > 0) {
                        $skipped++;
                        continue;
                    }
                    $getParent->execute([$item['values']['MA_LOP']]);
                    $lopId = $getParent->fetchColumn();
                    if (!$lopId) {
                        throw new RuntimeException("Lớp '{$item['values']['MA_LOP']}' không tồn tại.");
                    }
                    $year = !empty($item['values']['NIEN_KHOA']) ? (int)$item['values']['NIEN_KHOA'] : null;
                    $insert->execute([$item['values']['MA_SV'], $item['values']['HOTEN'], $year, (int)$lopId]);
                    $inserted++;
                }
            } elseif ($dataset === 'VIPHAM') {
                $students = $pdo->query('SELECT UPPER(RTRIM(LTRIM(MA_SV))) as code, ID FROM SINHVIEN')->fetchAll(PDO::FETCH_KEY_PAIR);
                $types = $pdo->query('SELECT ID, TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX FROM LOI_VIPHAM')->fetchAll();
                $typeMap = [];
                foreach ($types as $t) {
                    $tName = $this->decodeHexUnicode($t['TEN_LOI_HEX'] ?? null, $t['TEN_LOI']);
                    $typeMap[mb_strtolower($tName, 'UTF-8')] = (int)$t['ID'];
                }
                $semesters = $pdo->query('SELECT MA_HK, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY ORDER BY NGAY_BAT_DAU DESC')->fetchAll(PDO::FETCH_ASSOC);

                $insert = $pdo->prepare(
                    'INSERT INTO CT_VIPHAM (ID_LOI, ID_SV, NGAY_VIPHAM, THOI_DIEM, KHU_VUC_ID, GHI_CHU, MA_HK)
                     VALUES (:type, :student, :day, :thoidiem, :area, :note, :ma_hk)'
                );

                foreach ($rows as $item) {
                    if ($item['status'] !== 'valid') {
                        $skipped++;
                        continue;
                    }
                    $val = $item['values'];
                    $codeUpper = strtoupper(trim((string)($val['MA_SV'] ?? '')));
                    $studentId = $students[$codeUpper] ?? null;
                    if (!$studentId) {
                        $skipped++;
                        continue;
                    }

                    $typeId = $val['_type_id'] ?? ($typeMap[mb_strtolower((string)($val['TEN_LOI'] ?? ''), 'UTF-8')] ?? 1);
                    $thoiDiem = $val['THOI_DIEM'] ?? date('Y-m-d H:i:s');
                    $ngay = $val['_ngay'] ?? substr($thoiDiem, 0, 10);
                    $areaId = $val['_area_id'] ?? null;
                    $room = !empty($val['VI_TRI']) ? (string)$val['VI_TRI'] : null;
                    if ($areaId === null && $room !== null && $room !== '') {
                        $areaId = $this->matchAreaIdByName($room);
                    }
                    $note = 'Nhập biên bản từ file Excel';

                    $maHk = null;
                    foreach ($semesters as $sem) {
                        if ($ngay >= $sem['NGAY_BAT_DAU'] && $ngay <= $sem['NGAY_KET_THUC']) {
                            $maHk = $sem['MA_HK'];
                            break;
                        }
                    }

                    $insert->execute([
                        'type' => $typeId,
                        'student' => $studentId,
                        'day' => $ngay,
                        'thoidiem' => $thoiDiem,
                        'area' => $areaId,
                        'note' => $note,
                        'ma_hk' => $maHk,
                    ]);
                    $inserted++;
                }
            }

            $pdo->commit();
            @unlink($cacheFile);
            return [
                'inserted' => $inserted,
                'skipped_duplicates' => $skipped,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function downloadNormalizedPreview(string $previewToken): array
    {
        $cacheFile = storage_path('app/import_previews/' . preg_replace('/[^a-zA-Z0-9]/', '', $previewToken) . '.json');
        if (!file_exists($cacheFile)) {
            throw new RuntimeException('Phiên xem trước dữ liệu không tồn tại hoặc đã hết hạn.');
        }

        $data = json_decode((string)file_get_contents($cacheFile), true);
        if (!$data || !is_array($data)) {
            throw new RuntimeException('Dữ liệu xem trước không hợp lệ.');
        }

        $dataset = strtoupper((string)($data['dataset'] ?? 'SINHVIEN'));
        $dbHeaders = $this->importColumns()[$dataset] ?? ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'];
        $friendlyHeaders = $this->importHeaderLabels($dataset);

        $exportRows = [];
        foreach ($data['rows'] ?? [] as $r) {
            $row = [];
            foreach ($dbHeaders as $h) {
                $row[] = $r['values'][$h] ?? '';
            }
            $exportRows[] = $row;
        }

        $xlsx = $this->createXlsx($friendlyHeaders, $exportRows);
        return [
            'dataset' => $dataset,
            'content' => $xlsx,
        ];
    }

    public function createXlsx(array $headers, array $rows): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Máy chủ chưa bật PHP ZipArchive extension.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'cim_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể khởi tạo file zip tạm.');
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Dữ liệu" sheetId="1" r:id="rId1"/></sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/>'
            . '</xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            . '<alignment vertical="center"/>'
            . '</xf>'
            . '</cellXfs>'
            . '</styleSheet>');

        $allRows = array_merge([$headers], $rows);
        $colWidths = [];
        foreach ($allRows as $r) {
            foreach (array_values($r) as $columnIndex => $value) {
                $len = mb_strwidth((string)$value, 'UTF-8');
                $colWidths[$columnIndex] = max($colWidths[$columnIndex] ?? 12, min(50, $len + 5));
            }
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if (!empty($colWidths)) {
            $sheet .= '<cols>';
            foreach ($colWidths as $colIdx => $width) {
                $cNum = $colIdx + 1;
                $sheet .= '<col min="' . $cNum . '" max="' . $cNum . '" width="' . $width . '" customWidth="1"/>';
            }
            $sheet .= '</cols>';
        }

        $sheet .= '<sheetData>';
        foreach ($allRows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $isHeader = ($number === 1);
            $styleId = $isHeader ? 1 : 2;
            $rowHeight = $isHeader ? ' ht="26" customHeight="1"' : ' ht="20" customHeight="1"';

            $sheet .= '<row r="' . $number . '"' . $rowHeight . '>';
            foreach (array_values($row) as $columnIndex => $value) {
                $letters = '';
                $column = $columnIndex + 1;
                while ($column > 0) {
                    $column--;
                    $letters = chr(65 + ($column % 26)) . $letters;
                    $column = intdiv($column, 26);
                }
                $escaped = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $sheet .= '<c r="' . $letters . $number . '" s="' . $styleId . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);
        return (string)$content;
    }

    public function verifyResetPassword(string $password): bool
    {
        $expected = (string)config('cim.reset_password');
        if ($expected === '') {
            return false;
        }
        if (str_starts_with($expected, '$2y$') || str_starts_with($expected, '$2a$')) {
            return password_verify($password, $expected);
        }
        return hash_equals($expected, $password);
    }

    /**
     * Reset vi phạm của HỌC KỲ HIỆN TẠI (không đụng tới lịch sử các học kỳ trước).
     * Bản ghi Camera AI chỉ bị xóa khi ảnh bằng chứng đã được job retention dọn (ANH_KHOA NULL)
     * để không bỏ sót object trên Cloudflare R2.
     */
    public function resetAllViolations(bool $includeCameraRecords = false): array
    {
        $pdo = $this->getPdo();
        $semester = $pdo->prepare('SELECT TOP 1 MA_HK, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY
                                   WHERE :d BETWEEN NGAY_BAT_DAU AND NGAY_KET_THUC ORDER BY NGAY_BAT_DAU DESC');
        $semester->execute(['d' => date('Y-m-d')]);
        $current = $semester->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            throw new InvalidArgumentException('Chưa khai báo học kỳ hiện tại nên không thể reset. Vui lòng thêm học kỳ trong mục Danh mục.');
        }

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM CT_VIPHAM WHERE MA_HK = :hk');
            $del->execute(['hk' => $current['MA_HK']]);
            $ctCount = $del->rowCount();

            $bgCount = 0;
            $keptWithEvidence = 0;
            if ($includeCameraRecords) {
                $range = ['from' => $current['NGAY_BAT_DAU'], 'to' => $current['NGAY_KET_THUC']];
                $kept = $pdo->prepare('SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM p JOIN ANH_VIPHAM a ON a.PHAT_HIEN_ID = p.ID
                                       WHERE CAST(p.THOI_DIEM AS DATE) BETWEEN :from AND :to AND a.ANH_KHOA IS NOT NULL');
                $kept->execute($range);
                $keptWithEvidence = (int)$kept->fetchColumn();

                $delCam = $pdo->prepare('DELETE p FROM PHAT_HIEN_VIPHAM p
                                         WHERE CAST(p.THOI_DIEM AS DATE) BETWEEN :from AND :to
                                           AND NOT EXISTS (SELECT 1 FROM ANH_VIPHAM a WHERE a.PHAT_HIEN_ID = p.ID AND a.ANH_KHOA IS NOT NULL)');
                $delCam->execute($range);
                $bgCount = $delCam->rowCount();
            }

            $pdo->commit();
            return [
                'success' => true,
                'semester' => (string)$current['MA_HK'],
                'deleted_student_violations' => $ctCount,
                'deleted_camera_records' => $bgCount,
                'kept_camera_records_with_evidence' => $keptWithEvidence,
                'camera_reset_applied' => $includeCameraRecords,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
