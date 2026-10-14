<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
student_start_session();

// Logout changes state, so it only accepts POST with the CSRF token (header menu form).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !cim_verify_form_csrf()) {
    header('Location: ' . (cim_current_user() ? 'home.php' : 'login.php'), true, 303);
    exit;
}

cim_logout();
header('Location: login.php?logged_out=1', true, 303);
exit;
