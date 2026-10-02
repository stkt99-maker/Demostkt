<?php
declare(strict_types=1);

// หน้าตั้งค่าข้อความ/แบรนดิ้ง — เข้าใช้ได้เฉพาะผู้ที่ล็อกอินแล้วเท่านั้น
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

session_start();
if (empty($_SESSION['zbx_auth_ok'])) { header('Location: login.php'); exit; }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

require_once __DIR__ . '/lib/i18n.php';   // โหลดภาษา + Settings (ผ่าน i18n)
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['zbx_user'])) {
    header('Location: login.php');
    exit;
}

define('CUSTOM_LOGO_UPLOADED', 'assets/custom_logo.png');

// ===== ประมวลผล POST =====
$msg = null;
$is_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid CSRF token');
    }

    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'reset') {
        Settings::save([], '');
        if (is_file(__DIR__ . '/' . CUSTOM_LOGO_UPLOADED)) {
            @unlink(__DIR__ . '/' . CUSTOM_LOGO_UPLOADED);
        }
        header('Location: settings.php?reset=1');
        exit;
    }

    // ปกติ: action = save
    $texts = is_array($_POST['texts'] ?? null) ? $_POST['texts'] : [];

    $logoPath = Settings::logoPath(); // คงค่าโลโก้เดิมไว้ถ้าไม่มีการอัปโหลดใหม่

    // อัปโหลดโลโก้ใหม่ (PNG/JPG เท่านั้น, ≤ 1MB, ชื่อไฟล์ปลายทางคงที่)
    if (!empty($_FILES['logo_file']['name']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['logo_file']['tmp_name'];
        $size = (int)$_FILES['logo_file']['size'];
        $info = @getimagesize($tmp);
        if ($size > 1024 * 1024) {
            $msg = 'รูปใหญ่เกิน 1 MB'; $is_error = true;
        } elseif ($info === false || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            $msg = 'รองรับเฉพาะไฟล์ PNG หรือ JPG'; $is_error = true;
        } else {
            if (!move_uploaded_file($tmp, __DIR__ . '/' . CUSTOM_LOGO_UPLOADED)) {
                $msg = 'ย้ายไฟล์โลโก้ไม่สำเร็จ (ตรวจสิทธิ์โฟลเดอร์ assets/)'; $is_error = true;
            } else {
                $logoPath = CUSTOM_LOGO_UPLOADED;
            }
        }
    }

    // ติ๊ก "กลับไปใช้โลโก้จาก config" — ลบไฟล์ที่อัปโหลดและเคลียร์ค่า (มีผลก่อนการอัปโหลดใหม่)
    if (!empty($_POST['reset_logo'])) {
        if (is_file(__DIR__ . '/' . CUSTOM_LOGO_UPLOADED)) {
            @unlink(__DIR__ . '/' . CUSTOM_LOGO_UPLOADED);
        }
        $logoPath = '';
    }

    if (!$is_error) {
        try {
            Settings::save($texts, $logoPath);
            header('Location: settings.php?saved=1');
            exit;
        } catch (RuntimeException $e) {
            $msg = 'บันทึกไม่สำเร็จ: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $is_error = true;
        }
    }
}

// ===== ค่าที่แสดงในฟอร์ม =====
$overrides = Settings::textOverrides();
$effLogo   = Settings::logoPath() !== '' ? Settings::logoPath()
           : (defined('CUSTOM_LOGO_PATH') ? CUSTOM_LOGO_PATH : 'assets/nti_logo.png');

if (isset($_GET['saved'])) { $msg = 'บันทึกค่าเรียบร้อย'; $is_error = false; }
elseif (isset($_GET['reset'])) { $msg = 'กลับค่าเริ่มต้นทั้งหมดแล้ว'; $is_error = false; }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>ตั้งค่าหน้าฟอร์ม</title>
<style>
:root {
  --bg-light: #fff; --card-light: #f8f9fa; --text-dark: #333;
  --text-muted-light: #6c757d; --zbx-red: #e04646; --card-border-light: #e0e0e0; --input-light: #fff;
  --bg-dark: #1a1a1a; --card-dark: #2b2b2b; --text-light: #fff;
  --text-muted-dark: #ccc; --card-border-dark: #444; --input-dark: #3a3a3a;
}
* { box-sizing: border-box; }
body { margin: 0; font: 14px/1.45 system-ui,Segoe UI,Arial; transition: background .3s, color .3s; }
body.light-theme { background: var(--bg-light); color: var(--text-dark); }
body.dark-theme { background: var(--bg-dark); color: var(--text-light); }
.wrap { max-width: 760px; margin: 40px auto; padding: 0 16px; }
.card { border-radius: 14px; width: 100%; padding: 30px; }
body.light-theme .card { background: var(--card-light); border: 1px solid var(--card-border-light); box-shadow: 0 8px 30px rgba(0,0,0,.15); }
body.dark-theme .card { background: var(--card-dark); border: 1px solid var(--card-border-dark); box-shadow: 0 8px 30px rgba(0,0,0,.4); }
h1 { margin: 0 0 4px; font-size: 22px; color: var(--zbx-red); }
h2 { font-size: 16px; margin: 0 0 4px; border-bottom: 2px solid var(--zbx-red); padding-bottom: 6px; }
.section { margin-top: 26px; }
label { display: block; margin: .6rem 0 .2rem; font-weight: 600; }
input[type=text], input[type=file] { width: 100%; padding: .55rem .7rem; border-radius: 10px; border: 1px solid; }
body.light-theme input[type=text] { border-color: #ccc; background: var(--input-light); color: var(--text-dark); }
body.dark-theme input[type=text] { border-color: #444; background: var(--input-dark); color: var(--text-light); }
.muted { font-size: 13px; }
body.light-theme .muted { color: var(--text-muted-light); }
body.dark-theme .muted { color: var(--text-muted-dark); }
.btn { display: inline-block; margin-top: 14px; padding: .7rem 1.2rem; border: 0; border-radius: 10px; background: var(--zbx-red); color: #fff; font-weight: 700; cursor: pointer; text-decoration: none; }
.btn:hover { opacity: .9; }
.btn-gray { background: #6c757d; }
.btn-outline { background: transparent; border: 1px solid var(--zbx-red); color: var(--zbx-red); }
.notice { padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-weight: 600; }
.notice-ok { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
.notice-err { background: #fdecea; color: #b71c1c; border: 1px solid #f5c6cb; }
.logo-preview { max-width: 120px; height: auto; display: block; margin: 8px 0; padding: 6px; background: #fff; border: 1px solid var(--card-border-light); border-radius: 8px; }
.theme-switcher { position: absolute; top: 20px; right: 20px; background: none; border: 1px solid; padding: 5px 10px; border-radius: 5px; font-size: 14px; cursor: pointer; }
body.light-theme .theme-switcher { color: #555; background-color: #f0f0f0; border-color: #ccc; }
body.dark-theme .theme-switcher { color: #ccc; background-color: #3a3a3a; border-color: #444; }
</style>
</head>
<body class="light-theme">
<button id="theme-toggle" class="theme-switcher">Dark Theme</button>
<div class="wrap">
  <div class="card">
    <h1>⚙ ตั้งค่าหน้าฟอร์ม</h1>
    <div class="muted">แก้ข้อความ/แบรนดิ้งที่แสดงบนหน้าฟอร์มและใน PDF — ไม่ต้องแก้โค้ด</div>

    <?php if ($msg !== null): ?>
      <div class="notice <?= $is_error ? 'notice-err' : 'notice-ok' ?>" style="margin-top:14px;"><?= $msg ?></div>
    <?php endif; ?>

    <form method="post" action="settings.php" enctype="multipart/form-data" style="margin-top:10px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="save">

      <div class="section">
        <h2>ข้อความหน้าฟอร์ม</h2>
        <div class="muted">เว้นว่าง = ใช้ค่าเริ่มต้นเดิม (ตัวอย่างในช่องคือค่าปัจจุบันจากไฟล์ภาษา)</div>
        <?php foreach (Settings::EDITABLE_KEYS as $key => $label): if (str_starts_with($key, 'pdf_')) continue; ?>
          <label for="t-<?= $key ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
          <input type="text" id="t-<?= $key ?>" name="texts[<?= $key ?>]"
                 value="<?= htmlspecialchars($overrides[$key] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 placeholder="<?= htmlspecialchars(t($key), ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
      </div>

      <div class="section">
        <h2>ข้อความใน PDF</h2>
        <?php foreach (Settings::EDITABLE_KEYS as $key => $label): if (!str_starts_with($key, 'pdf_')) continue; ?>
          <label for="t-<?= $key ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
          <input type="text" id="t-<?= $key ?>" name="texts[<?= $key ?>]"
                 value="<?= htmlspecialchars($overrides[$key] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 placeholder="<?= htmlspecialchars(t($key), ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
      </div>

      <div class="section">
        <h2>โลโก้</h2>
        <div class="muted">ใช้ในหน้าฟอร์ม หน้าล็อกอิน และหัวกระดาษ PDF (PNG/JPG ขนาดไม่เกิน 1MB)</div>
        <img src="<?= htmlspecialchars($effLogo, ENT_QUOTES, 'UTF-8') ?>" alt="โลโก้ปัจจุบัน" class="logo-preview">
        <label for="logo-file">อัปโหลดโลโก้ใหม่</label>
        <input type="file" id="logo-file" name="logo_file" accept="image/png,image/jpeg">
        <label style="font-weight:400;margin-top:10px;">
          <input type="checkbox" name="reset_logo" value="1" style="width:auto;">
          ลบโลโก้ที่อัปโหลด — กลับไปใช้โลโก้จาก config.php
        </label>
      </div>

      <button class="btn" type="submit">บันทึก</button>
      <a class="btn btn-outline" href="export.php" style="margin-left:6px;">กลับหน้าฟอร์ม</a>
      <a class="btn btn-gray" href="logout.php" style="margin-left:6px;">⎋ <?= t('export_logout') ?></a>
    </form>

    <form method="post" action="settings.php" style="margin-top:10px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="reset">
      <button class="btn btn-gray" type="submit">กลับค่าเริ่มต้นทั้งหมด</button>
    </form>
  </div>
</div>

<script>
  (() => {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    function setTheme(theme) {
        body.classList.remove('light-theme', 'dark-theme');
        body.classList.add(theme + '-theme');
        themeToggle.textContent = (theme === 'dark' ? 'Light Theme' : 'Dark Theme');
        localStorage.setItem('theme', theme);
    }
    themeToggle.addEventListener('click', () => setTheme(body.classList.contains('dark-theme') ? 'light' : 'dark'));
    setTheme(localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  })();
</script>
</body>
</html>
