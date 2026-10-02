<?php
// lang/en.php
return [
    // General
    'theme_dark' => 'Dark Theme',
    'theme_light' => 'Light Theme',
    'error_invalid_session' => 'Invalid session',
    'error_server_error' => 'Server error',

    // login.php
    'login_title' => 'Zabbix Login',
    'login_heading' => 'Sign in',
    'login_subheading' => 'The frontend uses your user. The API uses a service account.',
    'login_user_label' => 'Zabbix User',
    'login_pass_label' => 'Password',
    'login_button' => 'Sign in',
    'login_error_invalid_form' => 'Invalid form',
    'login_error_invalid_credentials' => 'Invalid credentials',
    'login_error_frontend_rejected' => 'Zabbix frontend rejected the login',

    // export.php
    'export_title' => 'Export Zabbix PDF',
    'export_logged_in_as' => 'You are logged in as',
    'export_hosts_label' => 'Hosts (one per line or comma-separated)',
    'export_hosts_placeholder' => 'host1, host2, host3',
    'export_groups_label' => 'Host Groups (one per line or comma-separated)',
    'export_groups_placeholder' => 'group1, group2, group3',
    'export_templates_items_label' => 'Templates and Items',
    'export_templates_items_placeholder' => 'Template OS Linux, Template App Nginx...',
    'export_from_label' => 'From:',
    'export_to_label' => 'To:',
    'export_last_24h' => 'Last 24 hours',
    'export_last_1d' => 'Last 1 day',
    'export_last_1w' => 'Last 1 week',
    'export_last_1m' => 'Last 1 month',
    'export_last_1y' => 'Last 1 year',
    'export_time_range_note' => 'If you fill From/To, absolute range will be used. Otherwise, the relative option is used.',
    'export_generate_pdf_button' => 'Generate PDF',
    'export_logout' => 'Log out',
    'export_templates_only_label' => 'Templates',
    'export_items_label' => 'Items to include in the report (tick to select/remove)',
    'items_search_placeholder' => 'Search items...',
    'items_select_all' => 'Select all',
    'items_clear_all' => 'Clear all',
    'items_panel_empty' => 'No items yet — select Hosts first, or pick from templates above',

    // Modals (JavaScript)
    'modal_select_button' => 'Select',
    'modal_cancel_button' => 'Cancel',
    'modal_next_button' => 'Next',
    'modal_back_button' => 'Back',
    'modal_add_items_button' => 'Add Items',
    'modal_loading' => 'Loading...',
    'modal_no_results' => 'No results found.',
    'modal_error_loading' => 'Error loading data.',
    'modal_select_hosts_title' => 'Select Hosts',
    'modal_filter_hosts_placeholder' => 'Filter hosts...',
    'modal_select_groups_title' => 'Select Host Groups',
    'modal_filter_groups_placeholder' => 'Filter groups...',
    'modal_select_templates_title' => 'Select Templates',
    'modal_filter_templates_placeholder' => 'Filter templates...',
    'modal_select_items_title' => 'Select Items',
    'modal_filter_items_placeholder' => 'Filter items...',
    'alert_select_template' => 'Please, select at least one template.',
    'alert_select_item' => 'Please, select at least one item.',

    // generate.php
    'generate_invalid_input' => 'You must provide at least one host or group, and one item.',
    'generate_invalid_range' => 'Invalid time range.',
    'generate_no_hosts_found' => 'No hosts were found with the provided data.',
    'generate_web_login_failed' => 'Could not log in to Zabbix web interface to download graphs.',
    'generate_no_graphs' => 'No valid graphs were generated. Check your selection, permissions, and time range.',
    'generate_pdf_failed' => 'Failed to generate PDF',
    
    // get_*.php errors
    'get_hosts_error' => 'Could not get hosts from Zabbix API.',
    'get_groups_error' => 'Could not get host groups from Zabbix API.',
    'get_items_error' => 'Could not get items from Zabbix API.',

    // ... (existing translations)
    
    // PdfBuilder.php
    'pdf_main_title' => 'บริษัท นิวเทคโนโลยี่อินฟอร์เมชั่น จำกัด',
    'pdf_toc_title' => 'Table of Contents',
    'pdf_generated_on' => 'Generated on',
    'pdf_page_x_of_y' => 'Page {PAGE_NUM} of {PAGE_COUNT}',

    // Final analysis section
    'pdf_analysis_title' => 'Summary and Data Analysis',
    'pdf_analysis_overview' => 'This report covers {HOSTS} hosts, {ITEMS} items, from {FROM} to {TO}',
    'pdf_analysis_obs_title' => 'Observations',
    'pdf_col_item' => 'Item',
    'pdf_col_host' => 'Host',
    'pdf_col_min' => 'Min',
    'pdf_col_avg' => 'Avg',
    'pdf_col_max' => 'Max',
    'pdf_col_last' => 'Last',
    'pdf_col_trend' => 'Trend',
    'pdf_trend_up' => 'Increasing',
    'pdf_trend_down' => 'Decreasing',
    'pdf_trend_stable' => 'Stable',
    'pdf_obs_cpu_high' => 'CPU: {NAME} averaged {AVG}% (peak {MAX}%) — above the 80% threshold',
    'pdf_obs_mem_high' => 'Memory: {NAME} averaged {AVG}% (peak {MAX}%) — above the 85% threshold',
    'pdf_obs_disk_high' => 'Disk: {NAME} averaged {AVG}% (peak {MAX}%) — above the 90% threshold',
    'pdf_obs_top3' => 'Items with the highest averages: {LIST}',
    'pdf_obs_all_normal' => 'No values exceeded the configured thresholds — the system behaved normally during the reported period',

    // ... (existing translations)
    
    // Author Credits
    'common_author_credit' => 'Developed by Axel Del Canto',
    'pdf_author_credit' => 'PDF Developed by Axel Del Canto',
    
    // ... (existing modal translations)
    'modal_add_items_button' => 'Add Items',
    'modal_select_page_button' => 'Select Page', // <-- ADD
    'modal_deselect_page_button' => 'Deselect Page', // <-- ADD
    'modal_loading' => 'Loading...',
];