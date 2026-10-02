<?php
// lib/Settings.php — ค่าที่ผู้ใช้แก้เองผ่านหน้า settings.php เก็บเป็นไฟล์ JSON
// (โครงการนี้ไม่มีฐานข้อมูล — รูปแบบเดียวกับที่แอปใช้เขียนไฟล์ชั่วคราว)
declare(strict_types=1);

class Settings
{
    /** คีย์ข้อความที่อนุญาตให้แก้ (whitelist) พร้อมป้ายอธิบายสำหรับฟอร์มตั้งค่า */
    public const EDITABLE_KEYS = [
        // หน้าฟอร์ม export
        'export_title'                      => 'หัวข้อหน้าฟอร์ม',
        'export_hosts_label'                => 'ป้ายช่อง Hosts',
        'export_hosts_placeholder'          => 'ตัวอย่างในช่อง Hosts (placeholder)',
        'export_groups_label'               => 'ป้ายช่อง Host Groups',
        'export_groups_placeholder'         => 'ตัวอย่างในช่อง Host Groups',
        'export_templates_items_label'      => 'ป้ายช่อง Templates and Items',
        'export_templates_items_placeholder'=> 'ตัวอย่างในช่อง Templates and Items',
        'export_from_label'                 => 'ป้ายช่อง From',
        'export_to_label'                   => 'ป้ายช่อง To',
        'export_last_24h'                   => 'ปุ่ม Last 24 hours',
        'export_time_range_note'            => 'หมายเหตุใต้ช่องเวลา',
        'export_generate_pdf_button'        => 'ปุ่ม Generate PDF',
        'common_author_credit'              => 'ข้อความท้ายหน้าฟอร์ม',
        // ข้อความใน PDF
        'pdf_main_title'                    => 'ชื่อบริษัทบนหัวกระดาษ PDF',
        'pdf_toc_title'                     => 'หัวข้อสารบัญใน PDF',
        'pdf_author_credit'                 => 'ข้อความท้ายกระดาษ PDF',
    ];

    private static function file(): string
    {
        return __DIR__ . '/../data/settings.json';
    }

    /** อ่านไฟล์ JSON — คืน [] ถ้ายังไม่มีไฟล์หรือไฟล์เสีย (ไม่ให้แอปล้ม) */
    public static function load(): array
    {
        $raw = @file_get_contents(self::file());
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** @return array<string,string> เฉพาะคีย์ที่อยู่ใน whitelist เท่านั้น */
    public static function textOverrides(): array
    {
        $out = [];
        $texts = self::load()['texts'] ?? [];
        foreach ($texts as $k => $v) {
            if (array_key_exists($k, self::EDITABLE_KEYS) && is_string($v)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /** พาธโลโก้ที่อัปโหลด (แบบ relative ต่อ root โครงการ) — '' ถ้าไม่ได้ตั้ง */
    public static function logoPath(): string
    {
        $p = self::load()['logo_path'] ?? '';
        return is_string($p) ? $p : '';
    }

    /**
     * บันทึกค่า — คีย์ที่ไม่อยู่ใน whitelist หรือค่าว่างจะถูกข้าม
     * (ค่าว่าง = กลับไปใช้ค่าเริ่มต้นจากไฟล์ภาษา/config)
     */
    public static function save(array $texts, string $logoPath): void
    {
        $clean = [];
        foreach ($texts as $k => $v) {
            if (array_key_exists($k, self::EDITABLE_KEYS) && is_string($v)
                && trim($v) !== '' && mb_check_encoding($v, 'UTF-8')) {
                $clean[$k] = trim($v);
            }
        }

        $dir = dirname(self::file());
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create data directory');
        }

        $data = ['texts' => $clean, 'logo_path' => $logoPath];
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Cannot encode settings as JSON');
        }
        $tmp = self::file() . '.tmp';
        if (file_put_contents($tmp, $json, LOCK_EX) === false
            || !@rename($tmp, self::file())) {
            throw new RuntimeException('Cannot write settings file');
        }
    }
}
