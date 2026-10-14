<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/catalog_module.php';

student_start_session();
student_require_user($_SERVER['REQUEST_METHOD'] !== 'GET');

try {
    $pdo = student_db();
} catch (Throwable $error) {
    error_log('Catalog database connection failure (' . get_class($error) . ').');
    student_json_error('Chưa thể kết nối cơ sở dữ liệu danh mục. Kiểm tra cấu hình máy chủ.', 503);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $entities = ['KHOA', 'NGANH', 'LOP', 'LOI_VIPHAM'];
        $result = [];
        foreach ($entities as $entity) {
            $result[$entity] = catalog_data($pdo, $entity);
        }
        student_json_response([
            'csrf_token' => student_csrf_token(),
            'role' => $_SESSION['role'] ?? null,
            'catalogs' => $result,
        ]);
    } catch (PDOException $error) {
        error_log('Catalog read failure (' . get_class($error) . ').');
        student_json_error('Không thể đọc dữ liệu danh mục từ cơ sở dữ liệu.', 503);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    student_json_error('Phương thức HTTP không được hỗ trợ.', 405);
}

student_verify_csrf();
$body = student_decode_json_body();
$entity = strtoupper((string)($body['entity'] ?? ''));
$action = $body['action'] ?? '';

// Violation types drive AI ingestion and reports: only administrators may change them.
if ($entity === 'LOI_VIPHAM' && ($_SESSION['role'] ?? null) !== 'ADMIN') {
    student_json_error('Chỉ Quản trị viên được thay đổi loại vi phạm.', 403);
}

try {
    $pdo->beginTransaction();
    if ($action === 'save') {
        $id = catalog_save($pdo, $entity, $body['id'] ?? null,
            is_array($body['record'] ?? null) ? $body['record'] : []);
        $pdo->commit();
        student_json_response(['success' => true, 'id' => $id], 201);
    }
    if ($action === 'delete') {
        catalog_delete($pdo, $entity, $body['id'] ?? null);
        $pdo->commit();
        student_json_response(['success' => true]);
    }
    $pdo->rollBack();
    student_json_error('Thao tác danh mục không được hỗ trợ.', 422);
} catch (InvalidArgumentException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    student_json_error($error->getMessage(), 422);
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (str_starts_with((string)$error->getCode(), '23')) {
        student_json_error(
            $action === 'delete'
                ? 'Danh mục đang được dữ liệu khác tham chiếu hoặc còn vi phạm; không thể xóa.'
                : 'Mã hoặc tên danh mục đã tồn tại. Không có thay đổi nào được lưu.',
            409
        );
    }
    error_log('Catalog write failure (' . get_class($error) . ').');
    student_json_error('Không thể lưu thay đổi danh mục. Transaction đã được hủy.', 503);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Catalog operation failure (' . get_class($error) . ').');
    student_json_error('Không thể hoàn tất thao tác danh mục.', 500);
}
