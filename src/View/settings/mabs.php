<!DOCTYPE html>
<?php
// التحقق من الجلسة والصلاحيات
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['مدير', 'admin'])) {
    http_response_code(403);
    echo '<h2 style="color:red;text-align:center;margin-top:50px">غير مصرح لك بالدخول لهذه الصفحة</h2>';
    exit;
}
?>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خريطة النظام - نظام إنجاز الإعلامية</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Font Awesome & Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            direction: rtl;
            line-height: 1.6;
        }

        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .page-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .system-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .section-icon {
            background: var(--primary-gradient);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-left: 1rem;
        }

        .section-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
        }

        .section-description {
            color: #6c757d;
            margin: 0;
            font-size: 0.95rem;
        }

        .file-structure {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .file-tree {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem;
            border-radius: 10px;
            overflow-x: auto;
            white-space: pre;
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .workflow-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .workflow-card:hover {
            transform: translateY(-5px);
        }

        .workflow-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }

        .workflow-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .workflow-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .permissions-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .table {
            margin: 0;
        }

        .table th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .api-routes {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .route-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .route-method {
            background: var(--success-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 1rem;
        }

        .route-path {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #2c3e50;
        }

        .route-description {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .notification-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .notification-description {
            opacity: 0.9;
            margin: 0;
        }

        .btn-custom {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .footer-custom {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
            border-radius: 15px 15px 0 0;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .workflow-grid {
                grid-template-columns: 1fr;
            }

            .route-item {
                flex-direction: column;
                text-align: center;
            }

            .route-method {
                margin-left: 0;
                margin-bottom: 0.5rem;
            }

            .section-header {
                flex-direction: column;
                text-align: center;
            }

            .section-icon {
                margin-left: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- هيكل الملفات -->
        <div class="system-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-folder"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-tree me-2"></i>
                        هيكل الملفات
                    </h3>
                    <p class="section-description">
                        تنظيم الملفات والمجلدات في النظام
                    </p>
                </div>
            </div>

            <div class="file-structure">
                <div class="file-tree">new_injaz/
├── ajax_order_actions.php
├── ajax_update_payment.php
├── api_tasks.php
├── assign_workshop_orders.php
├── composer.json
├── composer.lock
├── dashboard.php
├── index.php
├── README.md
├── security_headers.php
├── service-worker.js
├── app/
│   └── Controller/
│       └── EmployeeController.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── logoenjaz.jpg
│   │   ├── sidebar.js
│   │   └── style.css
│   └── css/
│       └── admin.css
├── src/
│   ├── footer.php
│   ├── header.php
│   ├── routes.php
│   ├── Api/
│   │   ├── ApiController.php
│   │   ├── FilterTasks.php
│   │   ├── SaveSubscription.php
│   │   └── SearchClient.php
│   │   └── UpdateRating.php
│   ├── Auth/
│   │   ├── Login.php
│   │   └── Logout.php
│   ├── Controller/
│   │   ├── ClientController.php
│   │   ├── EmployeeController.php
│   │   └── OrderController.php
│   │   └── ProductController.php
│   ├── Core/
│   │   ├── AuthCheck.php
│   │   ├── Database.php
│   │   ├── Helpers.php
│   │   ├── InitialTasksQuery.php
│   │   ├── MessageSystem.php
│   │   ├── Permissions.php
│   │   └── PushNotification.php
│   ├── Payments/
│   ├── Reports/
│   │   ├── Financial.php
│   │   ├── Stats.php
│   │   └── Timeline.php
│   └── View/
│       ├── dashboard.php
│       ├── employee_advanced_reports.php
│       ├── financial_report.php
│       ├── login_form.php
│       ├── stats_report.php
│       ├── timeline_report.php
│       ├── client/
│       │   ├── confirm_delete.php
│       │   ├── form.php
│       │   └── list.php
│       ├── employee/
│       │   ├── delete_confirm.php
│       │   ├── form.php
│       │   └── list.php
│       │   └── permissions.php
│       ├── order/
│       │   ├── form.php
│       │   └── list.php
│       ├── product/
│       │   ├── confirm_delete.php
│       │   ├── form.php
│       │   └── list.php
│       └── task/
│           └── card.php
└── vendor/
    ├── autoload.php
    ├── brick/
    │   └── math/
    ├── composer/
    ├── graham-campbell/
    │   └── result-type/
    ├── guzzlehttp/
    │   ├── guzzle/
    │   ├── promises/
    │   └── psr7/
    ├── minishlink/
    │   └── web-push/
    ├── phpopption/
    │   └── phpopption/
    ├── psr/
    │   ├── clock/
    │   ├── http-client/
    │   ├── http-factory/
    │   ├── http-message/
    │   └── ralouphie/
    │       └── getallheaders/
    ├── spomky-labs/
    │   ├── base64url/
    │   └── pki-framework/
    ├── symfony/
    │   ├── deprecation-contracts/
    │   ├── polyfill-ctype/
    │   ├── polyfill-mbstring/
    │   └── polyfill-php80/
    ├── vlucas/
    │   └── phpdotenv/
    └── web-token/
        └── jwt-library/</div>
            </div>
        </div>

        <!-- سير العمل -->
        <div class="system-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-arrow-right-circle"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-diagram-2 me-2"></i>
                        سير العمل
                    </h3>
                    <p class="section-description">
                        خطوات العمل الأساسية في النظام
                    </p>
                </div>
            </div>

            <div class="workflow-grid">
                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--success-gradient);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h5 class="workflow-title">إدارة العملاء</h5>
                    <p class="workflow-description">
                        إضافة وتعديل وحذف بيانات العملاء والتواصل معهم
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--warning-gradient);">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <h5 class="workflow-title">إدارة الطلبات</h5>
                    <p class="workflow-description">
                        إنشاء وتتبع وإدارة الطلبات من البداية إلى التسليم
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--danger-gradient);">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="workflow-title">إدارة الموظفين</h5>
                    <p class="workflow-description">
                        إدارة بيانات الموظفين والصلاحيات والمهام المسندة
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--primary-gradient);">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h5 class="workflow-title">التقارير والإحصائيات</h5>
                    <p class="workflow-description">
                        عرض التقارير المالية والإحصائيات والتحليلات
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--success-gradient);">
                        <i class="bi bi-bell"></i>
                    </div>
                    <h5 class="workflow-title">الإشعارات</h5>
                    <p class="workflow-description">
                        إرسال واستقبال الإشعارات والتنبيهات المهمة
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-icon" style="background: var(--warning-gradient);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="workflow-title">الأمان والصلاحيات</h5>
                    <p class="workflow-description">
                        إدارة الأمان والصلاحيات وحماية البيانات
                    </p>
                </div>
            </div>
        </div>

        <!-- جدول الصلاحيات -->
        <div class="system-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-key me-2"></i>
                        جدول الصلاحيات
                    </h3>
                    <p class="section-description">
                        الصلاحيات المتاحة لكل دور في النظام
                    </p>
                </div>
            </div>

            <div class="permissions-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الدور</th>
                            <th>الصلاحيات</th>
                            <th>الوصف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>مدير</strong></td>
                            <td>
                                <span class="badge bg-success">قراءة</span>
                                <span class="badge bg-primary">كتابة</span>
                                <span class="badge bg-warning">تعديل</span>
                                <span class="badge bg-danger">حذف</span>
                            </td>
                            <td>صلاحيات كاملة على جميع أجزاء النظام</td>
                        </tr>
                        <tr>
                            <td><strong>موظف</strong></td>
                            <td>
                                <span class="badge bg-success">قراءة</span>
                                <span class="badge bg-primary">كتابة</span>
                                <span class="badge bg-warning">تعديل</span>
                            </td>
                            <td>صلاحيات محدودة حسب المهام المسندة</td>
                        </tr>
                        <tr>
                            <td><strong>عميل</strong></td>
                            <td>
                                <span class="badge bg-success">قراءة</span>
                            </td>
                            <td>صلاحيات عرض الطلبات والمعلومات الخاصة فقط</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- مسارات API -->
        <div class="system-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-router"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-code-slash me-2"></i>
                        مسارات API
                    </h3>
                    <p class="section-description">
                        نقاط النهاية المتاحة في واجهة برمجة التطبيقات
                    </p>
                </div>
            </div>

            <div class="api-routes">
                <div class="route-item">
                    <div class="route-method">GET</div>
                    <div>
                        <div class="route-path">/api/tasks</div>
                        <div class="route-description">الحصول على قائمة المهام</div>
                    </div>
                </div>

                <div class="route-item">
                    <div class="route-method">POST</div>
                    <div>
                        <div class="route-path">/api/orders</div>
                        <div class="route-description">إنشاء طلب جديد</div>
                    </div>
                </div>

                <div class="route-item">
                    <div class="route-method">PUT</div>
                    <div>
                        <div class="route-path">/api/orders/{id}</div>
                        <div class="route-description">تحديث بيانات الطلب</div>
                    </div>
                </div>

                <div class="route-item">
                    <div class="route-method">DELETE</div>
                    <div>
                        <div class="route-path">/api/orders/{id}</div>
                        <div class="route-description">حذف الطلب</div>
                    </div>
                </div>

                <div class="route-item">
                    <div class="route-method">GET</div>
                    <div>
                        <div class="route-path">/api/clients</div>
                        <div class="route-description">الحصول على قائمة العملاء</div>
                    </div>
                </div>

                <div class="route-item">
                    <div class="route-method">POST</div>
                    <div>
                        <div class="route-path">/api/notifications</div>
                        <div class="route-description">إرسال إشعار</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- نظام الإشعارات -->
        <div class="notification-section">
            <div class="d-flex align-items-center">
                <div class="notification-icon">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <div>
                    <h4 class="notification-title">نظام الإشعارات</h4>
                    <p class="notification-description">
                        النظام يدعم إرسال الإشعارات الفورية والمؤجلة للمستخدمين والعملاء
                    </p>
                </div>
            </div>
        </div>

        <!-- روابط مفيدة -->
        <div class="system-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-link-45deg"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-link me-2"></i>
                        روابط مفيدة
                    </h3>
                    <p class="section-description">
                        روابط للصفحات والأدوات المهمة في النظام
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-grid">
                        <a href="data-guide.php" class="btn-custom">
                            <i class="bi bi-database"></i>
                            دليل البيانات
                        </a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-grid">
                        <a href="role_filters_new.php" class="btn-custom">
                            <i class="bi bi-funnel"></i>
                            إعدادات الفلترة
                        </a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-grid">
                        <a href="test.php" class="btn-custom">
                            <i class="bi bi-tools"></i>
                            أدوات الاختبار
                        </a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-grid">
                        <a href="dashboard.php" class="btn-custom">
                            <i class="bi bi-house"></i>
                            لوحة التحكم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; 2025 نظام إنجاز الإعلامية - خريطة النظام</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
