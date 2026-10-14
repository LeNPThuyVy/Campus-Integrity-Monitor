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
                } catch (Throwable) {
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

    public function today(): DateTimeImmutable
    {
        $minutes = (int)env('CIM_UTC_OFFSET_MINUTES', 420);
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
                       l.MA_LOP, n.MA_NGANH, k.MA_KHOA, k.TEN_KHOA, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHOA AS VARBINARY(MAX)), 2) AS TEN_KHOA_HEX,
                       (SELECT COUNT_BIG(*) FROM CT_VIPHAM v WHERE v.ID_SV = s.ID) AS SO_VI_PHAM'
            . $base . $filter
            . ' ORDER BY s.MA_SV OFFSET :offset ROWS FETCH NEXT :page_size ROWS ONLY';

        $query = $pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $query->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->bindValue(':page_size', $pageSize, PDO::PARAM_INT);
        $query->execute();
        $rows = $query->fetchAll();
        foreach ($rows as &$r) {
            $r['HOTEN'] = $this->decodeHexUnicode($r['HOTEN_HEX'] ?? null, $r['HOTEN']);
            $r['TEN_KHOA'] = $this->decodeHexUnicode($r['TEN_KHOA_HEX'] ?? null, $r['TEN_KHOA']);
            unset($r['HOTEN_HEX'], $r['TEN_KHOA_HEX']);
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
    public function getViolations(int $studentId): array
    {
        $pdo = $this->getPdo();
        $statement = $pdo->prepare(
            'SELECT v.ID, v.ID_LOI, l.TEN_LOI, CONVERT(VARCHAR(MAX), CAST(l.TEN_LOI AS VARBINARY(MAX)), 2) AS TEN_LOI_HEX, l.DIEM_TRU, v.NGAY_VIPHAM, v.GHI_CHU, CONVERT(VARCHAR(MAX), CAST(v.GHI_CHU AS VARBINARY(MAX)), 2) AS GHI_CHU_HEX, v.NGAY_TAO
             FROM CT_VIPHAM v INNER JOIN LOI_VIPHAM l ON l.ID = v.ID_LOI
             WHERE v.ID_SV = :student
             ORDER BY v.NGAY_VIPHAM DESC, v.ID DESC'
        );
        $statement->execute(['student' => $studentId]);
        $vRows = $statement->fetchAll();
        foreach ($vRows as &$vr) {
            $vr['TEN_LOI'] = $this->decodeHexUnicode($vr['TEN_LOI_HEX'] ?? null, $vr['TEN_LOI']);
            $vr['GHI_CHU'] = $this->decodeHexUnicode($vr['GHI_CHU_HEX'] ?? null, $vr['GHI_CHU']);
            unset($vr['TEN_LOI_HEX'], $vr['GHI_CHU_HEX']);
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

    public function addViolation(array $data): array
    {
        $pdo = $this->getPdo();
        $studentId = (int)($data['student_id'] ?? 0);
        $typeId = (int)($data['type_id'] ?? 0);
        $date = trim((string)($data['date'] ?? ''));
        $note = !empty($data['note']) ? trim((string)$data['note']) : null;
        $confirmDuplicate = ($data['confirm_duplicate'] ?? false) === true;

        if ($studentId <= 0 || $typeId <= 0 || $date === '') {
            throw new InvalidArgumentException('Thông tin vi phạm không hợp lệ.');
        }

        $same = $pdo->prepare(
            'SELECT COUNT(*) FROM CT_VIPHAM WITH (UPDLOCK, HOLDLOCK)
             WHERE ID_SV = :student AND ID_LOI = :type AND NGAY_VIPHAM = :day'
        );
        $same->execute(['student' => $studentId, 'type' => $typeId, 'day' => $date]);
        if ((int)$same->fetchColumn() > 0 && !$confirmDuplicate) {
            return [
                'duplicate' => true,
                'message' => 'Sinh viên đã có vi phạm cùng loại trong ngày này. Xác nhận nếu đây là một lần vi phạm khác.',
                'code' => 'duplicate_same_day',
            ];
        }

        $insert = $pdo->prepare(
            'INSERT INTO CT_VIPHAM (ID_LOI, ID_SV, NGAY_VIPHAM, GHI_CHU) OUTPUT INSERTED.ID
             VALUES (:type, :student, :day, :note)'
        );
        $insert->bindValue(':type', $typeId, PDO::PARAM_INT);
        $insert->bindValue(':student', $studentId, PDO::PARAM_INT);
        $insert->bindValue(':day', $date);
        $insert->bindValue(':note', $note, $note === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
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
            'NGANH' => ['MA_NGANH', 'TEN_NGANH', 'MA_KHOA'],
            'LOP' => ['MA_LOP', 'MA_NGANH'],
            'SINHVIEN' => ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'],
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
            . '</Relationships>');

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $allRows = array_merge([$headers], $rows);
        foreach ($allRows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $sheet .= '<row r="' . $number . '">';
            foreach (array_values($row) as $columnIndex => $value) {
                $letters = '';
                $column = $columnIndex + 1;
                while ($column > 0) {
                    $column--;
                    $letters = chr(65 + ($column % 26)) . $letters;
                    $column = intdiv($column, 26);
                }
                $escaped = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $sheet .= '<c r="' . $letters . $number . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
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
}
