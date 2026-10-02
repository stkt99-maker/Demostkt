<?php
declare(strict_types=1);

// ออกจากระบบ: ลบ cookie jar ของ frontend Zabbix (มี zbx_session ข้างใน)
// แล้วทำลายเซสชันของแอป และส่งกลับหน้า login

session_start();

if (!defined('APP_TMP')) {
    define('APP_TMP', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zbx_pdf');
}

// cookiejar สร้างโดย login.php เป็น APP_TMP/cj_<12 hex>.txt เท่านั้น —
// จำกัดด้วย basename + regex กัน path traversal
if (!empty($_SESSION['zbx_cookiejar'])) {
    $cj = basename((string)$_SESSION['zbx_cookiejar']);
    if (preg_match('/^cj_[0-9a-f]{12}\.txt$/', $cj) && is_file(APP_TMP . '/' . $cj)) {
        @unlink(APP_TMP . '/' . $cj);
    }
}

$_SESSION = [];
session_destroy();

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

header('Location: login.php');
exit;
