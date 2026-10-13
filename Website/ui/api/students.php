<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/student_module.php';

student_start_session();
student_require_user($_SERVER['REQUEST_METHOD'] !== 'GET');

try {
    $pdo = student_db();
} catch (Throwable $error) {
    student_database_failure($error);
}

function student_filter_input(): array
{
    return [
        'search' => $_GET['search'] ?? '',
        'faculty' => $_GET['faculty'] ?? '',
        'major' => $_GET['major'] ?? '',
        'class' => $_GET['class'] ?? '',
        'page' => $_GET['page'] ?? 1,
        'page_size' => $_GET['page_size'] ?? STUDENT_PAGE_SIZE_DEFAULT,
    ];
}

function student_export(PDO $pdo): never
{
    $filters = student_filter_input();
    $filters['page'] = 1;
    $filters['page_size'] = STUDENT_PAGE_SIZE_MAX;
    $page = student_list($pdo, $filters);
    if ($page['total'] > 50000) {
        student_json_error('Kết quả vượt giới hạn xuất 50.000 sinh viên. Hãy lọc danh sách trước khi xuất.', 413);
    }
    $rows = $page['rows'];
    while (count($rows) < $page['total']) {
        $filters['page']++;
        $next = student_list($pdo, $filters);
        if (!$next['rows']) {
            break;
        }
        array_push($rows, ...$next['rows']);
    }

    $exportRows = [];
    foreach ($rows as $row) {
        $exportRows[] = [
            $row['MA_SV'],
            $row['HOTEN'],
            $row['NIEN_KHOA'],
            $row['MA_LOP'],
            $row['MA_NGANH'],
            $row['MA_KHOA'],
            $row['TEN_KHOA'],
            $row['SO_VI_PHAM'],
        ];
    }
    $content = student_create_xlsx(
        ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP', 'MA_NGANH', 'MA_KHOA', 'TEN_KHOA', 'SO_VI_PHAM'],
        $exportRows
    );
    student_download_xlsx('danh-sach-sinh-vien.xlsx', $content);
}

function student_download_template(string $dataset): never
{
    $columns = student_import_columns()[$dataset] ?? null;
    if ($columns === null) {
        student_json_error('Loại dữ liệu mẫu không hợp lệ.', 422);
    }
    $content = student_create_xlsx($columns, []);
    student_download_xlsx(strtolower($dataset) . '-template.xlsx', $content);
}

function student_detect_xlsx_upload(array $file): string
{
    if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size']) ||
        !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK ||
        !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        student_json_error('Không nhận được tệp upload hợp lệ.', 400);
    }
    if ((int)$file['size'] < 1 || (int)$file['size'] > STUDENT_IMPORT_MAX_BYTES) {
        student_json_error('Tệp phải có dung lượng lớn hơn 0 và không quá 15 MB.', 413);
    }
    $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'xlsx') {
        student_json_error('Chỉ hỗ trợ .xlsx. Định dạng .xls không được hỗ trợ trên môi trường hiện tại.', 415);
    }
    $handle = fopen($file['tmp_name'], 'rb');
    $signature = $handle ? fread($handle, 4) : false;
    if (is_resource($handle)) {
        fclose($handle);
    }
    if ($signature !== "PK\x03\x04") {
        student_json_error('Nội dung tệp không phải gói XLSX/ZIP hợp lệ.', 422);
    }
    if (!class_exists(finfo::class)) {
        student_json_error('Máy chủ chưa bật bộ kiểm tra MIME.', 503);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
    ], true)) {
        student_json_error('MIME của tệp không phải workbook XLSX.', 415);
    }
    return $file['tmp_name'];
}

function student_import_upload(PDO $pdo): never
{
    if (($_SERVER['CONTENT_LENGTH'] ?? 0) > STUDENT_IMPORT_MAX_BYTES + 1024 * 1024) {
        student_json_error('Yêu cầu upload vượt giới hạn dung lượng.', 413);
    }
    $dataset = strtoupper(trim((string)($_POST['dataset'] ?? '')));
    $temporaryPath = student_detect_xlsx_upload($_FILES['file'] ?? []);

    $archive = new ZipArchive();
    if ($archive->open($temporaryPath, ZipArchive::RDONLY) !== true) {
        student_json_error('Tệp không phải workbook XLSX hợp lệ.', 422);
    }
    try {
        if ($archive->locateName('xl/workbook.xml') === false ||
            $archive->locateName('xl/worksheets/sheet1.xml') === false ||
            $archive->locateName('[Content_Types].xml') === false) {
            student_json_error('Tệp thiếu cấu trúc workbook XLSX bắt buộc.', 422);
        }
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = (string)$archive->getNameIndex($i);
            if (str_contains($name, 'vbaProject') || str_contains($name, 'externalLinks') ||
                str_contains($name, 'embeddings') || str_contains($name, '../') ||
                str_starts_with($name, '/')) {
                student_json_error('Workbook có thành phần không được phép.', 422);
            }
            if (str_ends_with(strtolower($name), '.rels')) {
                $relationships = $archive->getFromIndex($i);
                if (is_string($relationships) && preg_match('/TargetMode\s*=\s*["\']External["\']/i', $relationships)) {
                    student_json_error('Workbook có liên kết ngoài không được phép.', 422);
                }
            }
        }
    } finally {
        $archive->close();
    }

    $sourceRows = student_parse_xlsx($temporaryPath, $dataset);
    try {
        $previewRows = student_import_preview($pdo, $dataset, $sourceRows);
    } catch (PDOException $error) {
        throw $error;
    }

    $token = bin2hex(random_bytes(24));
    $_SESSION['student_import_previews'][$token] = [
        'dataset' => $dataset,
        'rows' => $previewRows,
        'created_at' => time(),
    ];
    foreach ($_SESSION['student_import_previews'] as $key => $preview) {
        if (!is_array($preview) || ($preview['created_at'] ?? 0) < time() - STUDENT_PREVIEW_TTL) {
            unset($_SESSION['student_import_previews'][$key]);
        }
    }

    $counts = ['valid' => 0, 'duplicate' => 0, 'error' => 0];
    foreach ($previewRows as $row) {
        $counts[$row['status']]++;
    }
    student_json_response([
        'preview_token' => $token,
        'dataset' => $dataset,
        'total' => count($previewRows),
        'counts' => $counts,
        'can_commit' => $counts['error'] === 0,
        'rows' => $previewRows,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'init';
    try {
        switch ($action) {
            case 'csrf':
                student_json_response(['csrf_token' => student_csrf_token()]);
            case 'init':
                student_json_response([
                    'csrf_token' => student_csrf_token(),
                    'statistics' => student_statistics($pdo),
                    'catalog' => student_catalog($pdo),
                    'list' => student_list($pdo, student_filter_input()),
                ]);
            case 'list':
                student_json_response(student_list($pdo, student_filter_input()));
            case 'catalog':
                student_json_response(student_catalog($pdo));
            case 'detail':
                $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    student_json_error('Mã hồ sơ không hợp lệ.', 422);
                }
                $statement = $pdo->prepare(
                    'SELECT s.ID, s.MA_SV, s.HOTEN, s.NIEN_KHOA, l.MA_LOP, n.MA_NGANH,
                            k.MA_KHOA, k.TEN_KHOA,
                            (SELECT COUNT_BIG(*) FROM CT_VIPHAM v WHERE v.ID_SV = s.ID) AS SO_VI_PHAM
                     FROM SINHVIEN s
                     INNER JOIN LOP l ON l.ID = s.LOP_ID
                     INNER JOIN NGANH n ON n.ID = l.NGANH_ID
                     INNER JOIN KHOA k ON k.ID = n.KHOA_ID
                     WHERE s.ID = :id'
                );
                $statement->execute(['id' => $id]);
                $student = $statement->fetch();
                if (!$student) {
                    student_json_error('Không tìm thấy hồ sơ sinh viên.', 404);
                }
                student_json_response(['student' => $student]);
            case 'template':
                student_download_template(strtoupper((string)($_GET['dataset'] ?? '')));
            case 'export':
                student_export($pdo);
            default:
                student_json_error('Thao tác không được hỗ trợ.', 404);
        }
    } catch (PDOException $error) {
        student_database_failure($error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    student_verify_csrf();
    try {
        student_import_upload($pdo);
    } catch (PDOException $error) {
        student_database_failure($error);
    } catch (StudentWorkbookException $error) {
        student_json_error($error->getMessage(), $error->httpStatus);
    } catch (Throwable $error) {
        error_log('Student import processing failure (' . get_class($error) . ').');
        student_json_error('Không thể xử lý workbook. Hãy kiểm tra đúng template và thử lại.', 422);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    student_verify_csrf();
    $body = student_decode_json_body();
    $action = $body['action'] ?? '';
    try {
        if ($action === 'save') {
            $pdo->beginTransaction();
            $student = student_upsert($pdo, $body['student'] ?? []);
            $pdo->commit();
            student_json_response(['success' => true, 'student' => $student], 201);
        }
        if ($action === 'import-commit') {
            $token = $body['preview_token'] ?? null;
            if (!is_string($token) || !preg_match('/^[a-f0-9]{48}$/', $token)) {
                student_json_error('Mã preview không hợp lệ hoặc đã hết hạn.', 422);
            }
            $preview = $_SESSION['student_import_previews'][$token] ?? null;
            unset($_SESSION['student_import_previews'][$token]);
            if (!is_array($preview) || ($preview['created_at'] ?? 0) < time() - STUDENT_PREVIEW_TTL) {
                student_json_error('Preview đã hết hạn. Hãy chọn lại workbook.', 410);
            }
            foreach ($preview['rows'] as $row) {
                if (($row['status'] ?? '') === 'error') {
                    student_json_error('Import all-or-nothing: còn dòng lỗi nên không lưu bất kỳ dòng nào.', 422);
                }
            }
            $inserted = student_run_import_transaction($pdo, $preview['dataset'], $preview['rows']);
            student_json_response([
                'success' => true,
                'inserted' => $inserted,
                'skipped_duplicates' => count(array_filter(
                    $preview['rows'],
                    static fn(array $row): bool => $row['status'] === 'duplicate'
                )),
            ]);
        }
        student_json_error('Thao tác không được hỗ trợ.', 404);
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $state = (string)$error->getCode();
        if (str_starts_with($state, '23')) {
            student_json_error('Dữ liệu trùng hoặc vi phạm khóa ngoại. Không có thay đổi nào được lưu.', 409);
        }
        student_database_failure($error);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student write failed (' . get_class($error) . ').');
        student_json_error('Không thể lưu dữ liệu. Transaction đã được hủy.', 409);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    student_verify_csrf();
    $body = student_decode_json_body();
    try {
        $pdo->beginTransaction();
        student_delete($pdo, $body['id'] ?? null);
        $pdo->commit();
        student_json_response(['success' => true]);
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (str_starts_with((string)$error->getCode(), '23')) {
            student_json_error('Sinh viên còn dữ liệu vi phạm tham chiếu; không thể xóa hồ sơ.', 409);
        }
        student_database_failure($error);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Student delete failed (' . get_class($error) . ').');
        student_json_error('Không thể xóa hồ sơ sinh viên.', 409);
    }
}

student_json_error('Phương thức HTTP không được hỗ trợ.', 405);
