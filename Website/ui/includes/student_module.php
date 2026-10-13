<?php
declare(strict_types=1);

const STUDENT_PAGE_SIZE_DEFAULT = 10;
const STUDENT_PAGE_SIZE_MAX = 100;
const STUDENT_IMPORT_MAX_BYTES = 15 * 1024 * 1024;
const STUDENT_IMPORT_MAX_ROWS = 5000;
const STUDENT_PREVIEW_TTL = 1800;

final class StudentWorkbookException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}

function student_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('CIM_STUDENT_SESSION');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function student_require_user(bool $write = false): void
{
    student_start_session();
    $role = $_SESSION['role'] ?? null;
    $authenticated = ($_SESSION['authenticated'] ?? false) === true;

    if (!$authenticated || !in_array($role, ['ADMIN', 'CTSV'], true)) {
        student_json_error('Bạn cần đăng nhập bằng tài khoản được cấp quyền.', 401);
    }

    if ($write && !in_array($role, ['ADMIN', 'CTSV'], true)) {
        student_json_error('Bạn không có quyền thực hiện thao tác này.', 403);
    }
}

function student_csrf_token(): string
{
    student_start_session();
    if (!isset($_SESSION['student_csrf_token']) || !is_string($_SESSION['student_csrf_token'])) {
        $_SESSION['student_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['student_csrf_token'];
}

function student_verify_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($provided) || $provided === '' ||
        !hash_equals(student_csrf_token(), $provided)) {
        student_json_error('Phiên thao tác đã hết hạn. Hãy tải lại trang và thử lại.', 403);
    }
}

function student_json_error(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function student_json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function student_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!in_array('sqlsrv', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('SQL Server PDO driver is unavailable.');
    }

    $host = getenv('CIM_SQLSERVER_HOST');
    $database = getenv('CIM_SQLSERVER_DATABASE');
    $username = getenv('CIM_SQLSERVER_USERNAME');
    $password = getenv('CIM_SQLSERVER_PASSWORD');
    if (!$host || !$database || !$username || $password === false) {
        throw new RuntimeException('SQL Server connection settings are incomplete.');
    }

    $encrypt = getenv('CIM_SQLSERVER_ENCRYPT') ?: 'yes';
    $trustCertificate = getenv('CIM_SQLSERVER_TRUST_CERTIFICATE') ?: 'no';
    if (!in_array($encrypt, ['yes', 'no'], true) ||
        !in_array($trustCertificate, ['yes', 'no'], true)) {
        throw new RuntimeException('Invalid SQL Server TLS settings.');
    }

    $dsn = sprintf(
        'sqlsrv:Server={%s};Database={%s};Encrypt=%s;TrustServerCertificate=%s',
        str_replace('}', '}}', $host),
        str_replace('}', '}}', $database),
        $encrypt,
        $trustCertificate
    );
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function student_database_failure(Throwable $error): never
{
    error_log('Student module database failure (' . get_class($error) . ').');
    student_json_error('Không thể truy cập cơ sở dữ liệu sinh viên. Vui lòng thử lại sau.', 503);
}

function student_required_string(mixed $value, string $field, int $maxLength): string
{
    if (!is_string($value)) {
        student_json_error("Trường {$field} không hợp lệ.", 422);
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value, 'UTF-8') > $maxLength) {
        student_json_error("Trường {$field} là bắt buộc và không được vượt quá {$maxLength} ký tự.", 422);
    }
    return $value;
}

function student_code(mixed $value, string $field, int $maxLength): string
{
    $value = student_required_string($value, $field, $maxLength);
    if (preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
        student_json_error("Trường {$field} chỉ được chứa ký tự ASCII, số, dấu gạch dưới hoặc gạch ngang.", 422);
    }
    return $value;
}

function student_parse_year_value(mixed $value): ?int
{
    if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
        return null;
    }
    $value = (string)$value;
    $negative = str_starts_with($value, '-');
    $digits = ltrim($value, '-');
    $digits = ltrim($digits, '0');
    $digits = $digits === '' ? '0' : $digits;
    if (strlen($digits) > 10) {
        return null;
    }
    $normalized = ($negative && $digits !== '0' ? '-' : '') . $digits;
    $year = filter_var($normalized, FILTER_VALIDATE_INT);
    if ($year === false || $year < -2147483648 || $year > 2147483647) {
        return null;
    }
    return $year;
}

function student_year(mixed $value): int
{
    $year = student_parse_year_value($value);
    if ($year === null) {
        student_json_error('Niên khóa phải là số nguyên trong phạm vi cột SQL Server INT.', 422);
    }
    return $year;
}

function student_bind_like(string $value): string
{
    return str_replace(['\\', '%', '_', '['], ['\\\\', '\\%', '\\_', '\\['], $value);
}

function student_decode_json_body(): array
{
    $body = file_get_contents('php://input');
    if ($body === false || $body === '' || strlen($body) > 65536) {
        student_json_error('Nội dung yêu cầu không hợp lệ.', 400);
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        student_json_error('Nội dung JSON không hợp lệ.', 400);
    }
    return $decoded;
}

function student_catalog(PDO $pdo): array
{
    $faculties = $pdo->query('SELECT ID, MA_KHOA, TEN_KHOA FROM KHOA ORDER BY MA_KHOA')->fetchAll();
    $majors = $pdo->query(
        'SELECT n.ID, n.MA_NGANH, n.TEN_NGANH, n.KHOA_ID, k.MA_KHOA
         FROM NGANH n INNER JOIN KHOA k ON k.ID = n.KHOA_ID
         ORDER BY n.MA_NGANH'
    )->fetchAll();
    $classes = $pdo->query(
        'SELECT l.ID, l.MA_LOP, l.NGANH_ID, n.MA_NGANH, n.KHOA_ID, k.MA_KHOA
         FROM LOP l
         INNER JOIN NGANH n ON n.ID = l.NGANH_ID
         INNER JOIN KHOA k ON k.ID = n.KHOA_ID
         ORDER BY l.MA_LOP'
    )->fetchAll();

    return ['faculties' => $faculties, 'majors' => $majors, 'classes' => $classes];
}

function student_list(PDO $pdo, array $input): array
{
    $page = filter_var($input['page'] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($input['page_size'] ?? STUDENT_PAGE_SIZE_DEFAULT, FILTER_VALIDATE_INT);
    if ($page === false || $page < 1 || $page > 1000000 ||
        $pageSize === false || $pageSize < 1 || $pageSize > STUDENT_PAGE_SIZE_MAX) {
        student_json_error('Thông tin phân trang không hợp lệ.', 422);
    }

    $where = [];
    $params = [];
    $searchInput = $input['search'] ?? '';
    if (!is_string($searchInput)) {
        student_json_error('Từ khóa tìm kiếm không hợp lệ.', 422);
    }
    $search = trim($searchInput);
    if ($search !== '') {
        if (mb_strlen($search, 'UTF-8') > 100) {
            student_json_error('Từ khóa tìm kiếm quá dài.', 422);
        }
        $where[] = "(s.MA_SV LIKE :search_code ESCAPE '\\' OR s.HOTEN LIKE :search_name ESCAPE '\\')";
        $params['search_code'] = '%' . student_bind_like($search) . '%';
        $params['search_name'] = '%' . student_bind_like($search) . '%';
    }

    foreach ([
        'faculty' => ['k.MA_KHOA', 10],
        'major' => ['n.MA_NGANH', 10],
        'class' => ['l.MA_LOP', 20],
    ] as $key => [$column, $max]) {
        $filterInput = $input[$key] ?? '';
        if (!is_string($filterInput)) {
            student_json_error("Bộ lọc {$key} không hợp lệ.", 422);
        }
        $value = trim($filterInput);
        if ($value !== '') {
            $where[] = $column . ' = :' . $key;
            $params[$key] = student_code($value, $key, $max);
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

    $sql = 'SELECT s.ID, s.MA_SV, s.HOTEN, s.NIEN_KHOA,
                   l.MA_LOP, n.MA_NGANH, k.MA_KHOA, k.TEN_KHOA,
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

    return [
        'rows' => $query->fetchAll(),
        'page' => $page,
        'page_size' => $pageSize,
        'total' => $total,
        'pages' => max(1, (int)ceil($total / $pageSize)),
    ];
}

function student_find_class(
    PDO $pdo,
    string $classCode,
    ?string $facultyCode = null,
    ?string $majorCode = null
): int
{
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
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':class', $classCode);
    if ($facultyCode !== null) {
        $statement->bindValue(':faculty', $facultyCode);
    }
    if ($majorCode !== null) {
        $statement->bindValue(':major', $majorCode);
    }
    $statement->execute();
    $id = $statement->fetchColumn();
    if ($id === false) {
        student_json_error('Mã lớp không tồn tại hoặc không thuộc khoa đã chọn.', 422);
    }
    return (int)$id;
}

function student_upsert(PDO $pdo, array $data): array
{
    $studentId = $data['id'] ?? null;
    $code = student_code($data['ma_sv'] ?? null, 'MSSV', 10);
    $name = student_required_string($data['ho_ten'] ?? null, 'Họ và tên', 100);
    $year = student_year($data['nien_khoa'] ?? null);
    $classCode = student_code($data['ma_lop'] ?? null, 'Mã lớp', 20);
    $facultyCode = student_code($data['ma_khoa'] ?? null, 'Mã khoa', 10);
    $majorCode = student_code($data['ma_nganh'] ?? null, 'Mã ngành', 10);
    $classId = student_find_class($pdo, $classCode, $facultyCode, $majorCode);

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
            'class_id' => $classId,
        ]);
        return ['id' => (int)$insert->fetchColumn(), 'ma_sv' => $code];
    }

    $id = filter_var($studentId, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        student_json_error('Mã hồ sơ không hợp lệ.', 422);
    }
    $update = $pdo->prepare(
        'UPDATE SINHVIEN SET MA_SV = :code, HOTEN = :name, NIEN_KHOA = :year, LOP_ID = :class_id
         WHERE ID = :id'
    );
    $update->execute([
        'code' => $code,
        'name' => $name,
        'year' => $year,
        'class_id' => $classId,
        'id' => $id,
    ]);
    if ($update->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT 1 FROM SINHVIEN WHERE ID = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetchColumn()) {
            student_json_error('Không tìm thấy hồ sơ sinh viên.', 404);
        }
    }
    return ['id' => $id, 'ma_sv' => $code];
}

function student_delete(PDO $pdo, mixed $studentId): void
{
    $id = filter_var($studentId, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        student_json_error('Mã hồ sơ không hợp lệ.', 422);
    }
    $delete = $pdo->prepare('DELETE FROM SINHVIEN WHERE ID = :id');
    $delete->execute(['id' => $id]);
    if ($delete->rowCount() === 0) {
        student_json_error('Không tìm thấy hồ sơ sinh viên.', 404);
    }
}

function student_statistics(PDO $pdo): array
{
    return [
        'students' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM SINHVIEN')->fetchColumn(),
        'faculties' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM KHOA')->fetchColumn(),
        'classes' => (int)$pdo->query('SELECT COUNT_BIG(*) FROM LOP')->fetchColumn(),
    ];
}

function student_import_columns(): array
{
    return [
        'KHOA' => ['MA_KHOA', 'TEN_KHOA'],
        'NGANH' => ['MA_NGANH', 'TEN_NGANH', 'MA_KHOA'],
        'LOP' => ['MA_LOP', 'MA_NGANH'],
        'SINHVIEN' => ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'],
    ];
}

function student_import_preview(PDO $pdo, string $dataset, array $sourceRows): array
{
    $columnsByDataset = student_import_columns();
    if (!isset($columnsByDataset[$dataset])) {
        student_json_error('Loại dữ liệu import không hợp lệ.', 422);
    }

    $codeColumn = [
        'KHOA' => 'MA_KHOA',
        'NGANH' => 'MA_NGANH',
        'LOP' => 'MA_LOP',
        'SINHVIEN' => 'MA_SV',
    ][$dataset];
    $existing = [];
    $table = [
        'KHOA' => 'KHOA',
        'NGANH' => 'NGANH',
        'LOP' => 'LOP',
        'SINHVIEN' => 'SINHVIEN',
    ][$dataset];
    $codes = $pdo->query("SELECT {$codeColumn} FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($codes as $code) {
        $existing[(string)$code] = true;
    }

    $fkMaps = [];
    if ($dataset === 'NGANH') {
        $fkMaps['MA_KHOA'] = array_fill_keys(
            $pdo->query('SELECT MA_KHOA FROM KHOA')->fetchAll(PDO::FETCH_COLUMN),
            'exists'
        );
    } elseif ($dataset === 'LOP') {
        $fkMaps['MA_NGANH'] = array_fill_keys(
            $pdo->query('SELECT MA_NGANH FROM NGANH')->fetchAll(PDO::FETCH_COLUMN),
            'exists'
        );
    } elseif ($dataset === 'SINHVIEN') {
        $fkMaps['MA_LOP'] = array_fill_keys(
            $pdo->query('SELECT MA_LOP FROM LOP')->fetchAll(PDO::FETCH_COLUMN),
            'exists'
        );
    }

    $seenCodes = [];
    $preview = [];
    $duplicateNames = [];
    if ($dataset === 'KHOA') {
        $duplicateNames = array_fill_keys($pdo->query('SELECT TEN_KHOA FROM KHOA')->fetchAll(PDO::FETCH_COLUMN), true);
    } elseif ($dataset === 'NGANH') {
        $duplicateNames = array_fill_keys($pdo->query('SELECT TEN_NGANH FROM NGANH')->fetchAll(PDO::FETCH_COLUMN), true);
    }

    foreach ($sourceRows as $source) {
        $row = $source['values'];
        $errors = is_array($source['errors'] ?? null) ? $source['errors'] : [];
        $duplicateReasons = [];
        foreach ($columnsByDataset[$dataset] as $column) {
            if (!array_key_exists($column, $row) || trim((string)$row[$column]) === '') {
                $errors[] = "{$column}: không được để trống";
            }
        }

        $validated = [];
        foreach ($columnsByDataset[$dataset] as $column) {
            $value = trim((string)($row[$column] ?? ''));
            try {
                if (in_array($column, ['MA_KHOA', 'MA_NGANH', 'MA_LOP', 'MA_SV'], true)) {
                    $max = ['MA_KHOA' => 10, 'MA_NGANH' => 10, 'MA_LOP' => 20, 'MA_SV' => 10][$column];
                    if ($value !== '') {
                        if (mb_strlen($value, 'UTF-8') > $max ||
                            preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
                            $errors[] = "{$column}: mã không hợp lệ hoặc vượt quá {$max} ký tự";
                        } else {
                            $validated[$column] = $value;
                        }
                    }
                } elseif (in_array($column, ['TEN_KHOA', 'TEN_NGANH'], true)) {
                    $max = $column === 'TEN_KHOA' ? 50 : 50;
                    if ($value !== '' && mb_strlen($value, 'UTF-8') > $max) {
                        $errors[] = "{$column}: vượt quá {$max} ký tự";
                    } elseif ($value !== '') {
                        $validated[$column] = $value;
                    }
                } elseif ($column === 'HOTEN') {
                    if ($value !== '' && mb_strlen($value, 'UTF-8') > 100) {
                        $errors[] = 'HOTEN: vượt quá 100 ký tự';
                    } elseif ($value !== '') {
                        $validated[$column] = $value;
                    }
                } elseif ($column === 'NIEN_KHOA') {
                    $integerValue = student_parse_year_value($value);
                    if ($integerValue === null) {
                        $errors[] = 'NIEN_KHOA: cần số nguyên trong phạm vi SQL Server INT';
                    } else {
                        $validated[$column] = $integerValue;
                    }
                }
            } catch (Throwable) {
                $errors[] = "{$column}: giá trị không hợp lệ";
            }
        }

        foreach ($fkMaps as $column => $map) {
            $code = trim((string)($row[$column] ?? ''));
            if ($code !== '' && !isset($map[$code])) {
                $errors[] = "{$column}: mã quan hệ không tồn tại trong danh mục đã nhập trước";
            }
        }

        $code = (string)($validated[$codeColumn] ?? '');
        $duplicate = false;
        if ($code !== '' && isset($existing[$code])) {
            $duplicate = true;
            $duplicateReasons[] = "{$codeColumn}: mã đã tồn tại; sẽ bỏ qua";
        } elseif ($code !== '' && isset($seenCodes[$code])) {
            $duplicate = true;
            $duplicateReasons[] = "{$codeColumn}: mã trùng dòng trước; sẽ bỏ qua";
        }
        if ($code !== '') {
            $seenCodes[$code] = true;
        }

        $nameColumn = ['KHOA' => 'TEN_KHOA', 'NGANH' => 'TEN_NGANH'][$dataset] ?? null;
        if ($nameColumn !== null) {
            $name = (string)($validated[$nameColumn] ?? '');
            if ($name !== '' && (isset($duplicateNames[$name]) || isset($seenCodes['name:' . $name]))) {
                $duplicate = true;
                $duplicateReasons[] = "{$nameColumn}: tên đã tồn tại (cột có ràng buộc UNIQUE)";
            }
            if ($name !== '') {
                $seenCodes['name:' . $name] = true;
            }
        }

        $preview[] = [
            'row' => (int)$source['row'],
            'values' => $validated,
            'status' => $errors ? 'error' : ($duplicate ? 'duplicate' : 'valid'),
            'errors' => $errors ?: $duplicateReasons,
        ];
    }

    return $preview;
}

function student_import_commit(PDO $pdo, string $dataset, array $rows): int
{
    $columns = student_import_columns()[$dataset] ?? null;
    if ($columns === null) {
        throw new RuntimeException('Invalid import dataset.');
    }

    $insertSql = match ($dataset) {
        'KHOA' => 'INSERT INTO KHOA (MA_KHOA, TEN_KHOA) VALUES (:MA_KHOA, :TEN_KHOA)',
        'NGANH' => 'INSERT INTO NGANH (MA_NGANH, TEN_NGANH, KHOA_ID)
                    SELECT :MA_NGANH, :TEN_NGANH, ID FROM KHOA WHERE MA_KHOA = :MA_KHOA',
        'LOP' => 'INSERT INTO LOP (MA_LOP, NGANH_ID)
                  SELECT :MA_LOP, ID FROM NGANH WHERE MA_NGANH = :MA_NGANH',
        'SINHVIEN' => 'INSERT INTO SINHVIEN (MA_SV, HOTEN, NIEN_KHOA, LOP_ID)
                       SELECT :MA_SV, :HOTEN, :NIEN_KHOA, ID FROM LOP WHERE MA_LOP = :MA_LOP',
    };
    $statement = $pdo->prepare($insertSql);
    $inserted = 0;
    foreach ($rows as $row) {
        if (($row['status'] ?? '') !== 'valid') {
            continue;
        }
        $statement->execute($row['values']);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('A referenced import code disappeared before commit.');
        }
        $inserted++;
    }
    return $inserted;
}

function student_run_import_transaction(PDO $pdo, string $dataset, array $rows): int
{
    $pdo->exec('SET XACT_ABORT ON; SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    $pdo->beginTransaction();
    try {
        $inserted = student_import_commit($pdo, $dataset, $rows);
        $pdo->commit();
        return $inserted;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function student_excel_xml(string $content): SimpleXMLElement
{
    if (preg_match('/<!DOCTYPE|<!ENTITY/i', $content) === 1) {
        throw new StudentWorkbookException('Workbook có khai báo XML không được hỗ trợ.');
    }
    $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
    if (!$xml instanceof SimpleXMLElement) {
        throw new StudentWorkbookException('XML trong workbook không hợp lệ.');
    }
    return $xml;
}

function student_parse_xlsx(string $temporaryPath, string $dataset): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new StudentWorkbookException('Máy chủ chưa bật ZIP extension.', 503);
    }
    $columnsByDataset = student_import_columns();
    if (!isset($columnsByDataset[$dataset])) {
        throw new StudentWorkbookException('Loại dữ liệu import không hợp lệ.');
    }

    $zip = new ZipArchive();
    if ($zip->open($temporaryPath, ZipArchive::RDONLY) !== true) {
        throw new StudentWorkbookException('Tệp không phải workbook XLSX hợp lệ.');
    }
    try {
        $totalExpanded = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat) || ($stat['size'] ?? 0) > 20 * 1024 * 1024) {
                throw new StudentWorkbookException('Tệp Excel có thành phần vượt giới hạn an toàn.', 413);
            }
            $totalExpanded += (int)$stat['size'];
            if ($totalExpanded > 40 * 1024 * 1024) {
                throw new StudentWorkbookException('Tệp Excel nén vượt giới hạn giải nén an toàn.', 413);
            }
            if (str_contains((string)$stat['name'], '../') || str_starts_with((string)$stat['name'], '/')) {
                throw new StudentWorkbookException('Cấu trúc workbook không hợp lệ.');
            }
        }

        $sheetContents = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!is_string($sheetContents) || strlen($sheetContents) > 20 * 1024 * 1024) {
            throw new StudentWorkbookException('Không tìm thấy trang tính đầu tiên hoặc trang tính quá lớn.');
        }
        $sharedStrings = [];
        $sharedContents = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedContents)) {
            $sharedXml = student_excel_xml($sharedContents);
            foreach ($sharedXml->si as $item) {
                $text = '';
                foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $part) {
                    $text .= (string)$part;
                }
                $sharedStrings[] = $text;
            }
        }
        $sheet = student_excel_xml($sheetContents);
        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xmlRows = $sheet->xpath('//x:sheetData/x:row') ?: [];
        if (count($xmlRows) > STUDENT_IMPORT_MAX_ROWS + 1) {
            throw new StudentWorkbookException(
                'Workbook vượt giới hạn ' . STUDENT_IMPORT_MAX_ROWS . ' dòng dữ liệu.',
                413
            );
        }

        $rows = [];
        $headers = null;
        $expectedHeaders = $columnsByDataset[$dataset];
        foreach ($xmlRows as $xmlRow) {
            $rowNumber = (int)($xmlRow['r'] ?? 0);
            $cells = [];
            foreach ($xmlRow->c as $cell) {
                if (isset($cell->f)) {
                    throw new StudentWorkbookException('Không chấp nhận ô chứa công thức Excel.');
                }
                $reference = (string)($cell['r'] ?? '');
                if (preg_match('/^([A-Z]+)/', $reference, $match) !== 1) {
                    throw new StudentWorkbookException('Địa chỉ ô trong workbook không hợp lệ.');
                }
                $columnIndex = 0;
                foreach (str_split($match[1]) as $letter) {
                    $columnIndex = $columnIndex * 26 + ord($letter) - 64;
                }
                if ($columnIndex > count($expectedHeaders)) {
                    throw new StudentWorkbookException("Dòng {$rowNumber} có cột thừa ngoài mapping đã hỗ trợ.");
                }

                $type = (string)($cell['t'] ?? '');
                if ($type === 'inlineStr') {
                    $value = '';
                    foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $part) {
                        $value .= (string)$part;
                    }
                } else {
                    $raw = (string)($cell->v ?? '');
                    if ($type === 's') {
                        $index = filter_var($raw, FILTER_VALIDATE_INT);
                        if ($index === false || !array_key_exists($index, $sharedStrings)) {
                            throw new StudentWorkbookException("Dòng {$rowNumber} có shared string không hợp lệ.");
                        }
                        $value = $sharedStrings[$index];
                    } else {
                        $value = $raw;
                    }
                }
                $cells[$columnIndex] = [
                    'value' => trim($value),
                    'type' => $type !== '' ? $type : 'n',
                ];
            }

            if (!$cells || count(array_filter($cells, static fn($value) => $value !== '')) === 0) {
                continue;
            }
            ksort($cells);
            if ($headers === null) {
                $headers = [];
                foreach ($expectedHeaders as $index => $_) {
                    $headers[] = $cells[$index + 1]['value'] ?? '';
                }
                if ($headers !== $expectedHeaders) {
                    throw new StudentWorkbookException(
                        'Header không khớp. Yêu cầu chính xác theo thứ tự: ' . implode(', ', $expectedHeaders) . '.',
                    );
                }
                continue;
            }

            $values = [];
            $rowErrors = [];
            foreach ($expectedHeaders as $index => $header) {
                $cell = $cells[$index + 1] ?? ['value' => '', 'type' => 'n'];
                $values[$header] = $cell['value'];
                $allowedTypes = $header === 'NIEN_KHOA'
                    ? ['inlineStr', 's', 'str', 'n']
                    : ['inlineStr', 's', 'str'];
                if ($cell['value'] !== '' && !in_array($cell['type'], $allowedTypes, true)) {
                    $rowErrors[] = "{$header}: giá trị phải được lưu dạng văn bản trong Excel";
                }
            }
            $rows[] = ['row' => $rowNumber, 'values' => $values, 'errors' => $rowErrors];
        }
        if ($headers === null) {
            throw new StudentWorkbookException('Workbook rỗng hoặc thiếu dòng tiêu đề.');
        }
        if (!$rows) {
            throw new StudentWorkbookException('Workbook không có dòng dữ liệu.');
        }
        return $rows;
    } finally {
        $zip->close();
    }
}

function student_xlsx_cell(string $reference, string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
    $value = preg_replace('/^[\s\x{FEFF}]*([=+\-@])/u', "'$1", $value) ?? $value;
    return '<c r="' . $reference . '" t="inlineStr"><is><t xml:space="preserve">'
        . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        . '</t></is></c>';
}

function student_create_xlsx(array $headers, array $rows): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP ZIP extension is unavailable.');
    }
    $path = tempnam(sys_get_temp_dir(), 'cim-xlsx-');
    if ($path === false) {
        throw new RuntimeException('Unable to create temporary workbook.');
    }
    $zip = new ZipArchive();
    $opened = false;
    try {
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open temporary workbook.');
        }
        $opened = true;
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
                $sheet .= student_xlsx_cell($letters . $number, (string)$value);
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        $opened = false;
        $content = file_get_contents($path);
        if (!is_string($content)) {
            throw new RuntimeException('Unable to read generated workbook.');
        }
        return $content;
    } finally {
        if ($opened) {
            $zip->close();
        }
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function student_download_xlsx(string $filename, string $content): never
{
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store');
    echo $content;
    exit;
}
