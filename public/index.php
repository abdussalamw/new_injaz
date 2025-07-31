<?php
// public/index.php

// 1. تحميل الإعدادات الأساسية والاتصال بقاعدة البيانات
require_once __DIR__ . '/../src/db_connection_secure.php';

// 2. تضمين الهيدر
require_once __DIR__ . '/../src/header.php';

// 3. نظام التوجيه (Routing)
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';

switch ($page) {
    case 'clients':
        require_once __DIR__ . '/../src/Controller/ClientController.php';
        if ($action == 'add') {
            add_client();
        } else if ($action == 'edit') {
            edit_client();
        } else if ($action == 'delete') {
            delete_client();
        } else if ($action == 'ajax_add') {
            ajax_add_client();
        } else if ($action == 'ajax_get') {
            ajax_get_client();
        } else {
            list_clients();
        }
        break;
    case 'products':
        require_once __DIR__ . '/../src/Controller/ProductController.php';
        if ($action == 'add') {
            add_product();
        } else if ($action == 'edit') {
            edit_product();
        } else if ($action == 'delete') {
            delete_product();
        } else if ($action == 'ajax_add') {
            ajax_add_product();
        } else {
            list_products();
        }
        break;
    case 'employees':
        require_once __DIR__ . '/../src/Controller/EmployeeController.php';
        if ($action == 'add') {
            add_employee();
        } else if ($action == 'edit') {
            edit_employee();
        } else if ($action == 'delete') {
            delete_employee();
        } else if ($action == 'ajax_change_password') {
            ajax_change_password();
        } else {
            list_employees();
        }
        break;
    case 'orders':
        require_once __DIR__ . '/../src/Controller/OrderController.php';
        if ($action == 'add') {
            add_order();
        } else if ($action == 'edit') {
            edit_order();
        } else if ($action == 'delete') {
            delete_order();
        } else if ($action == 'ajax_filter_tasks') {
            ajax_filter_tasks();
        } else if ($action == 'ajax_actions') {
            ajax_order_actions();
        } else if ($action == 'ajax_update_payment') {
            ajax_update_payment();
        } else {
            list_orders();
        }
        break;
    case 'dashboard':
    default:
        echo "<div class='container'><h1>مرحباً بك في لوحة التحكم</h1></div>";
        break;
}

// 4. تضمين الفوتر
require_once __DIR__ . '/../src/footer.php';