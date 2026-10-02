# คู่มือติดตั้ง Zabbix PDF Report

เครื่องมือเลือก item จาก Zabbix แล้วส่งออกกราฟเป็นรายงาน PDF รองรับ Zabbix 6.4 ถึง 7.4+ ทดสอบติดตั้งจริงครบทุกขั้นตอนบน Zabbix 7.4.15 (Debian + nginx + PHP 8.5-FPM)

> 📖 **คู่มือการใช้งาน:** [USAGE.md](USAGE.md) — วิธีใช้ทุกฟีเจอร์ ตั้งแต่เลือกข้อมูล กำหนดช่วงเวลา อ่านหน้าสรุปท้ายเล่ม จนถึงหน้าตั้งค่า

## สิ่งที่ต้องมี

| รายการ | รายละเอียด |
|---|---|
| Zabbix frontend | เวอร์ชัน 6.4 – 7.4+ รันบน nginx หรือ Apache |
| PHP | 7.2 ขึ้นไป (ทดสอบแล้วบน 8.5) พร้อม extension: `curl`, `gd`, `mbstring`, `xml` |
| dompdf | มีพร้อมใน `vendor/` แล้ว ไม่ต้องรัน composer |
| user ใน Zabbix | สำหรับเชื่อม API แนะนำสร้าง user สิทธิ์ read-only แยกไว้ ไม่ควรใช้ Admin บนระบบจริง |

เซิร์ฟเวอร์ Zabbix ต้องเข้าถึงได้จากเบราว์เซอร์ของผู้ใช้ทั้งหน้าเว็บและ API (`api_jsonrpc.php`)

## ขั้นตอนติดตั้ง

### 1. วางโฟลเดอร์แอปใต้ web root ของ Zabbix

แอปนี้ต้องวาง "ใต้" ตำแหน่งที่ web server สั่งรัน PHP ได้เท่านั้น วางนอกที่จะเจอแค่ 404 หรือไฟล์ดิบ

ถ้าติดตั้ง Zabbix จากแพ็กเกจทางการบน Debian/Ubuntu พร้อม nginx ค่าตั้งต้นของ web root คือ `/usr/share/zabbix/ui`:

```bash
git clone https://github.com/stkt99-maker/Demostkt.git
sudo cp -r Demostkt/zabbix-pdf-report-7.0 /usr/share/zabbix/ui/zabbix-pdf-report
```

ใช้งานผ่าน `http://<ip-zabbix>/zabbix-pdf-report/`

ถ้าใช้ Apache หรือ frontend อยู่ใต้ path `/zabbix` ให้วางที่ `/usr/share/zabbix/zabbix-pdf-report` แล้ว URL จะเป็น `http://<ip-zabbix>/zabbix/zabbix-pdf-report/`

### 2. สร้าง config.php

```bash
cd /usr/share/zabbix/ui/zabbix-pdf-report
cp config.php.example config.php
```

แก้สามค่าหลักนี้:

```php
define('ZABBIX_URL', 'http://192.168.1.10');   // URL ของ Zabbix ไม่มี / ปิดท้าย
define('ZABBIX_API_USER', 'report_user');      // user สำหรับเรียก API
define('ZABBIX_API_PASS', 'รหัสผ่านจริง');
```

ค่าที่ปรับเพิ่มได้: `CUSTOM_LOGO_PATH` (โลโก้บนหัวรายงาน), `date_default_timezone_set()` (ให้ตรงกับเขตเวลาที่ตั้งไว้ใน Zabbix), `VERIFY_SSL` (ใช้ cert ที่ sign เองก็ปล่อย `false` ไว้ก่อน)

`config.php` มีรหัสผ่านอยู่ข้างใน อย่าลง git และอย่าเผยแพร่ (repo นี้กันไว้ใน `.gitignore` แล้ว)

### 3. สร้างไดเรกทอรีชั่วคราว แล้วมอบสิทธิ์

```bash
cd /usr/share/zabbix/ui/zabbix-pdf-report
mkdir -p tmp logs data
sudo chown -R www-data:www-data tmp logs data   # Debian/Ubuntu + nginx
# ถ้าใช้ RHEL/Rocky + Apache: chown apache:apache tmp logs data
```

สามโฟลเดอร์นี้เก็บของชั่วคราวและการตั้งค่า: `tmp` เก็บ cookie ของ session กับไฟล์กราฟชั่วคราว `data` เก็บการตั้งค่าที่แก้ผ่านหน้า settings.php กับรูปโลโก้ที่อัปโหลด ถ้าไม่สร้างเองแอปจะพยายามสร้างเองตอนรัน แต่สิทธิ์เขียนต้องพร้อม ไม่งั้นจะ login ไม่ผ่านหรือบันทึกการตั้งค่าไม่ได้

### 4. ตรวจความพร้อมด้วย check.php

เปิด `http://<ip-zabbix>/zabbix-pdf-report/check.php` — ทุกบรรทัดต้องขึ้น `[OK]` พอเจอ `FAIL` ก็แก้ตามที่ระบุ เช่น ติดตั้ง extension ที่ขาด (`php8.x-curl`, `php8.x-gd`, `php8.x-mbstring`) แล้วโหลดซ้ำ

รายการ `zip` ขึ้น `FAIL` ได้โดยไม่กระทบการทำงาน เพราะ dompdf ไม่ได้ใช้

### 5. เข้าใช้งาน

เปิด `http://<ip-zabbix>/zabbix-pdf-report/login.php` ล็อกอินด้วย user ของ Zabbix แล้วเริ่มสร้างรายงานได้เลย — วิธีใช้ทุกฟีเจอร์อยู่ใน [USAGE.md](USAGE.md)

เลือกข้อมูลได้สามทาง ใช้ร่วมกันก็ได้:

- **Hosts** — เลือกเครื่องตรง ๆ ผ่าน modal ยืนยันแล้วระบบเติมกลุ่มกับรายการ item ให้อัตโนมัติ
- **Host groups** — เลือกทั้งกลุ่ม ระบบจะดึงทุก host ในกลุ่มมาให้เอง
- **Templates → Items** — เลือก item ตาม template แล้วแอปค้นหา item ที่ตรงกันบนทุก host ที่เลือก คุมชุดสุดท้ายได้จากแผง Items บนหน้าฟอร์ม

รายงาน PDF ที่ได้จะมีสารบัญ กราฟต่อ item และหน้า "สรุปและวิเคราะห์ข้อมูล" ท้ายเล่ม (สถิติ ต่ำสุด/เฉลี่ย/สูงสุด/ล่าสุด/แนวโน้ม พร้อมข้อสังเกตอัตโนมัติ) ดูรายละเอียดทั้งหมดใน [USAGE.md](USAGE.md)

## ทางเลือก: เพิ่มปุ่ม PDF Report ในเมนูของ Zabbix

แก้ไฟล์ `/usr/share/zabbix/ui/include/classes/helpers/CMenuHelper.php` หาบรรทัด

```php
$submenu_reports = array_filter($submenu_reports);
```

แล้ววางบล็อกนี้ไว้เหนือบรรทัดนั้น:

```php
$submenu_reports[] = CWebUser::checkAccess(CRoleHelper::UI_REPORTS_SYSTEM_INFO)
    ? (new CMenuItem(_('PDF Report')))
        ->setUrl(new CUrl('zabbix-pdf-report/login.php'), true)
        ->setId('report_pdf')
        ->setAliases(['zabbix-pdf-report/login.php'])
    : null;
```

จากนั้น reload PHP-FPM (`systemctl reload php8.x-fpm`) เมนู **Reports** ก็จะมีรายการ PDF Report

ข้อควรรู้: นี่คือการแก้ไฟล์ของ frontend ตรง ๆ พออัปเกรดเวอร์ชัน Zabbix ข้ามไป ก็ต้องใส่บล็อกนี้ใหม่

## สำหรับผู้ใช้ Zabbix 7.2 และ 7.4

รุ่นนี้ส่ง token ผ่าน header `Authorization: Bearer` แล้ว เพราะ Zabbix 7.2 ตัดการรับค่า `auth` ใน body ของ JSON-RPC ออก (ส่งแบบเก่าจะเจอ error `-32600`) โค้ดชุดเดียวจึงใช้ได้ทั้ง 6.4, 7.0, 7.2 และ 7.4 ทดสอบครบวงจรแล้วบน 7.4.15

## ปัญหาที่พบบ่อย

| อาการ | สาเหตุและวิธีแก้ |
|---|---|
| หน้าขาว หรือ HTTP 500 | เปิดดู `logs/error.log` ในโฟลเดอร์แอป สาเหตุส่วนใหญ่อยู่ตรงนั้น |
| `token CSRF incorrecto` | กด F5 reload หน้า export หนึ่งครั้งแล้วใช้ต่อได้ปกติ รุ่นนี้ token มีอายุตลอด session ใช้หลายแท็บพร้อมกันก็ได้ |
| "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง" ทั้งที่พิมพ์ถูก | ลองผิดซ้ำเร็ว ๆ จะโดน brute-force protection ของ Zabbix บล็อกชั่วคราวราว 1 นาที รอแล้วลองใหม่ (รุ่นนี้โค้ดลองซ้ำเองหนึ่งครั้งให้ก่อนแล้ว) |
| `Database error occurred` | Zabbix รับงานไม่ทันชั่วขณะ โค้ด retry ให้เองหนึ่งครั้งแล้ว ถ้ายังเจออีกแปลว่าฐานข้อมูลตึงจริง รอสักครู่แล้วลองใหม่ |
| ได้ PDF แต่กราฟไม่ครบตามที่เลือก | แอปข้าม item ที่ไม่มีข้อมูลในช่วงเวลา หรือไม่มีอยู่บน host นั้น เช่น item ของ template Linux บนเครื่อง Windows — ลองเช็ค key ของ item กับแต่ละ host |
| login แล้วโดนดันกลับมาที่หน้าเดิมเรื่อย ๆ | session ของ PHP หมดอายุ (ค่าเริ่มต้น 24 นาที) log in ใหม่ หรือเพิ่มค่า `session.gc_maxlifetime` ใน php.ini |

## ถอนการติดตั้ง

ลบโฟลเดอร์ `zabbix-pdf-report` ออกจาก web root ก็จบ ถ้าเคยแก้ `CMenuHelper.php` ให้ลบบล็อกที่ใส่ไว้ออกด้วย

---

โครงการตั้งต้น: [axel250r/zabbix-pdf-report-7.0](https://github.com/axel250r/zabbix-pdf-report-7.0) (GPLv3) — รุ่นนี้ปรับให้ทำงานกับ Zabbix 7.4 และเพิ่มความทนทานให้ส่วนสร้างรายงาน
