<?php
// lang/th.php — คำแปลภาษาไทย
return [
    // General
    'theme_dark' => 'ธีมมืด',
    'theme_light' => 'ธีมสว่าง',
    'error_invalid_session' => 'เซสชันไม่ถูกต้อง',
    'error_server_error' => 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์',

    // login.php
    'login_title' => 'เข้าสู่ระบบ Zabbix',
    'login_heading' => 'เข้าสู่ระบบ',
    'login_subheading' => 'หน้าเว็บใช้บัญชีของคุณ ส่วน API ใช้บัญชีบริการ',
    'login_user_label' => 'ผู้ใช้ Zabbix',
    'login_pass_label' => 'รหัสผ่าน',
    'login_button' => 'เข้าสู่ระบบ',
    'login_error_invalid_form' => 'ฟอร์มไม่ถูกต้อง',
    'login_error_invalid_credentials' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
    'login_error_frontend_rejected' => 'หน้าเว็บ Zabbix ปฏิเสธการเข้าสู่ระบบ',

    // export.php
    'export_title' => 'ส่งออกรายงาน PDF Zabbix',
    'export_logged_in_as' => 'คุณล็อกอินเป็น',
    'export_hosts_label' => 'Hosts (หนึ่งชื่อต่อบรรทัด หรือคั่นด้วยเครื่องหมายจุลภาค)',
    'export_hosts_placeholder' => 'host1, host2, host3',
    'export_groups_label' => 'กลุ่ม Host (หนึ่งชื่อต่อบรรทัด หรือคั่นด้วยเครื่องหมายจุลภาค)',
    'export_groups_placeholder' => 'group1, group2, group3',
    'export_templates_items_label' => 'เทมเพลตและ Items',
    'export_templates_items_placeholder' => 'Template OS Linux, Template App Nginx...',
    'export_from_label' => 'จาก:',
    'export_to_label' => 'ถึง:',
    'export_last_24h' => '24 ชั่วโมงล่าสุด',
    'export_last_1d' => '1 วันล่าสุด',
    'export_last_1w' => '1 สัปดาห์ล่าสุด',
    'export_last_1m' => '1 เดือนล่าสุด',
    'export_last_1y' => '1 ปีล่าสุด',
    'export_time_range_note' => 'ถ้าระบุช่วงจาก-ถึง จะใช้ช่วงเวลานั้น ถ้าไม่ระบุจะใช้แบบสัมพัทธ์',
    'export_generate_pdf_button' => 'สร้าง PDF',
    'export_logout' => 'ออกจากระบบ',
    'export_templates_only_label' => 'เทมเพลต (Templates)',
    'export_items_label' => 'Items ที่จะทำในรายงาน (ติ๊กเลือก/ถอดออกได้)',
    'items_search_placeholder' => 'ค้นหา Items...',
    'items_select_all' => 'เลือกทั้งหมด',
    'items_clear_all' => 'ล้างทั้งหมด',
    'items_panel_empty' => 'ยังไม่มีรายการ — เลือก Hosts ก่อน หรือกดปุ่มเลือกจากเทมเพลตด้านบน',

    // Modals (JavaScript)
    'modal_select_button' => 'เลือก',
    'modal_cancel_button' => 'ยกเลิก',
    'modal_next_button' => 'ถัดไป',
    'modal_back_button' => 'กลับ',
    'modal_add_items_button' => 'เพิ่ม Items',
    'modal_loading' => 'กำลังโหลด...',
    'modal_no_results' => 'ไม่พบผลลัพธ์',
    'modal_error_loading' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล',
    'modal_select_hosts_title' => 'เลือก Hosts',
    'modal_filter_hosts_placeholder' => 'ค้นหา host...',
    'modal_select_groups_title' => 'เลือกกลุ่ม Host',
    'modal_filter_groups_placeholder' => 'ค้นหากลุ่ม...',
    'modal_select_templates_title' => 'เลือกเทมเพลต',
    'modal_filter_templates_placeholder' => 'ค้นหาเทมเพลต...',
    'modal_select_items_title' => 'เลือก Items',
    'modal_filter_items_placeholder' => 'ค้นหา item...',
    'alert_select_template' => 'กรุณาเลือกเทมเพลตอย่างน้อยหนึ่งรายการ',
    'alert_select_item' => 'กรุณาเลือก item อย่างน้อยหนึ่งรายการ',

    // generate.php
    'generate_invalid_input' => 'ต้องระบุ host หรือกลุ่มอย่างน้อยหนึ่งรายการ และ item อย่างน้อยหนึ่งรายการ',
    'generate_invalid_range' => 'ช่วงเวลาไม่ถูกต้อง',
    'generate_no_hosts_found' => 'ไม่พบ host ที่ตรงกับข้อมูลที่ระบุ',
    'generate_web_login_failed' => 'เข้าสู่ระบบหน้าเว็บ Zabbix เพื่อดาวน์โหลดกราฟไม่สำเร็จ',
    'generate_no_graphs' => 'ไม่ได้กราฟที่ใช้ได้ ตรวจสอบรายการที่เลือก สิทธิ์ และช่วงเวลา',
    'generate_pdf_failed' => 'สร้าง PDF ไม่สำเร็จ',

    // get_*.php errors
    'get_hosts_error' => 'ดึงรายการ host จาก Zabbix API ไม่สำเร็จ',
    'get_groups_error' => 'ดึงรายการกลุ่ม host จาก Zabbix API ไม่สำเร็จ',
    'get_items_error' => 'ดึงรายการ item จาก Zabbix API ไม่สำเร็จ',

    // PdfBuilder.php
    'pdf_main_title' => 'บริษัท นิวเทคโนโลยี่อินฟอร์เมชั่น จำกัด',
    'pdf_toc_title' => 'สารบัญ',
    'pdf_generated_on' => 'สร้างเมื่อ',
    'pdf_page_x_of_y' => 'หน้า {PAGE_NUM} จาก {PAGE_COUNT}',

    // Author credits
    'common_author_credit' => 'พัฒนาโดย Axel Del Canto',
    'pdf_author_credit' => 'PDF พัฒนาโดย Axel Del Canto',
];
