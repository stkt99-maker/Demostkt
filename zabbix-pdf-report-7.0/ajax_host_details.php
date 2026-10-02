<?php
declare(strict_types=1);

// ajax_host_details.php — ให้หน้า export ดึงข้อมูลของ hosts ที่เลือก (กลุ่ม,
// เทมเพลต, monitored items) เพื่อเติมช่อง Host Groups / Templates and Items
// อัตโนมัติเมื่อยืนยันการเลือก hosts

session_start();

if (empty($_SESSION['zbx_auth_ok'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => false, 'error' => 'invalid_session']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ZabbixApi.php';

header('Content-Type: application/json; charset=UTF-8');

// hostids มาเป็น JSON จาก JS (fallback: คั่นด้วย comma/space)
$raw = isset($_POST['hostids']) ? (string)$_POST['hostids'] : '';
$ids = [];
$tmp = json_decode($raw, true);
if (is_array($tmp)) {
    foreach ($tmp as $x) {
        $x = trim((string)$x);
        if ($x !== '' && ctype_digit($x)) $ids[] = $x;
    }
} else {
    foreach (preg_split('/[,\s]+/', $raw) as $x) {
        $x = trim($x);
        if ($x !== '' && ctype_digit($x)) $ids[] = $x;
    }
}
if (empty($ids)) {
    echo json_encode(['ok' => false, 'error' => 'no_hostids']);
    exit;
}

try {
    $api = new ZabbixApi(ZABBIX_API_URL, (string)$_SESSION['zbx_user'], (string)$_SESSION['zbx_pass']);

    $groups = (array)$api->call('hostgroup.get', ['output' => ['groupid', 'name'], 'hostids' => $ids]);
    $templates = (array)$api->call('template.get', ['output' => ['templateid', 'name'], 'hostids' => $ids]);
    $items = (array)$api->call('item.get', ['output' => ['key_', 'name'], 'hostids' => $ids, 'monitored' => true]);

    $groupList = [];
    foreach ($groups as $g) {
        if (!empty($g['groupid'])) $groupList[] = ['id' => (string)$g['groupid'], 'name' => (string)$g['name']];
    }
    $tplList = [];
    foreach ($templates as $tp) {
        if (!empty($tp['templateid'])) $tplList[] = ['id' => (string)$tp['templateid'], 'name' => (string)$tp['name']];
    }

    // คีย์ซ้ำระหว่าง hosts (host หลายตัวใช้เทมเพลตเดียวกัน) — ให้เหลือชุดเดียว
    $seen = [];
    $itemList = [];
    foreach ($items as $it) {
        $k = (string)($it['key_'] ?? '');
        if ($k === '' || isset($seen[$k])) continue;
        $seen[$k] = true;
        $itemList[] = ['key' => $k, 'name' => (string)($it['name'] ?? $k)];
    }
    usort($itemList, function ($a, $b) { return strcmp($a['name'], $b['name']); });

    echo json_encode([
        'ok' => true,
        'groups' => $groupList,
        'templates' => $tplList,
        'items' => $itemList,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
