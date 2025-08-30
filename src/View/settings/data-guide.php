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
    <title>دليل البيانات والنسخ الاحتياطي - نظام إنجاز الإعلامية</title>

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
            max-width: 1200px;
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

        .info-section {
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

        .data-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .data-box-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .data-icon {
            background: var(--success-gradient);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
            font-size: 1rem;
        }

        .data-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }

        .warning-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #f39c12;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .warning-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .warning-icon {
            background: #f39c12;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
        }

        .warning-title {
            color: #8b4513;
            font-weight: 600;
            margin: 0;
        }

        .danger-box {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #dc3545;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .danger-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .danger-icon {
            background: #dc3545;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
        }

        .danger-title {
            color: #721c24;
            font-weight: 600;
            margin: 0;
        }

        .success-box {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #28a745;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .success-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .success-icon {
            background: #28a745;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
        }

        .success-title {
            color: #155724;
            font-weight: 600;
            margin: 0;
        }

        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 1rem 0;
            border: 1px solid #34495e;
            overflow-x: auto;
        }

        .table-responsive {
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

        .step-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .step-number {
            background: var(--primary-gradient);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            margin-left: 1rem;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: #6c757d;
            margin: 0;
        }

        .backup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .backup-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .backup-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .backup-description {
            color: #6c757d;
            font-size: 0.9rem;
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

            .backup-grid {
                grid-template-columns: 1fr;
            }

            .step-item {
                flex-direction: column;
                text-align: center;
            }

            .step-number {
                margin-left: 0;
                margin-bottom: 1rem;
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
        <!-- هيكل قاعدة البيانات -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-table"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-diagram-3 me-2"></i>
                        هيكل قاعدة البيانات
                    </h3>
                    <p class="section-description">
                        الجداول الأساسية والعلاقات بينها في النظام
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الجدول</th>
                            <th>الوصف</th>
                            <th>الحقول المهمة</th>
                            <th>الاستخدام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>users</code></td>
                            <td>بيانات المستخدمين والموظفين</td>
                            <td>id, name, email, role, password</td>
                            <td>إدارة المستخدمين والصلاحيات</td>
                        </tr>
                        <tr>
                            <td><code>orders</code></td>
                            <td>بيانات الطلبات</td>
                            <td>id, client_id, designer_id, status, payment_status</td>
                            <td>إدارة الطلبات والمتابعة</td>
                        </tr>
                        <tr>
                            <td><code>clients</code></td>
                            <td>بيانات العملاء</td>
                            <td>id, name, phone, email, address</td>
                            <td>إدارة بيانات العملاء</td>
                        </tr>
                        <tr>
                            <td><code>notifications</code></td>
                            <td>الإشعارات</td>
                            <td>id, user_id, message, type, read_status</td>
                            <td>نظام الإشعارات</td>
                        </tr>
                        <tr>
                            <td><code>order_history</code></td>
                            <td>تاريخ الطلبات</td>
                            <td>id, order_id, action, user_id, timestamp</td>
                            <td>تتبع التغييرات</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- النسخ الاحتياطي -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-shield me-2"></i>
                        النسخ الاحتياطي
                    </h3>
                    <p class="section-description">
                        طرق وحلول حماية البيانات
                    </p>
                </div>
            </div>

            <div class="backup-grid">
                <div class="backup-card">
                    <div class="backup-icon" style="background: var(--success-gradient);">
                        <i class="bi bi-cloud-upload"></i>
                    </div>
                    <h5 class="backup-title">النسخ التلقائي</h5>
                    <p class="backup-description">
                        نسخ احتياطي تلقائي يومي لقاعدة البيانات والملفات المهمة
                    </p>
                </div>

                <div class="backup-card">
                    <div class="backup-icon" style="background: var(--warning-gradient);">
                        <i class="bi bi-hdd"></i>
                    </div>
                    <h5 class="backup-title">التخزين المحلي</h5>
                    <p class="backup-description">
                        حفظ النسخ الاحتياطية على أقراص تخزين خارجية آمنة
                    </p>
                </div>

                <div class="backup-card">
                    <div class="backup-icon" style="background: var(--danger-gradient);">
                        <i class="bi bi-cloud"></i>
                    </div>
                    <h5 class="backup-title">التخزين السحابي</h5>
                    <p class="backup-description">
                        رفع النسخ الاحتياطية إلى خدمات التخزين السحابي
                    </p>
                </div>
            </div>
        </div>

        <!-- خطوات النسخ الاحتياطي -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-list-ol me-2"></i>
                        خطوات النسخ الاحتياطي
                    </h3>
                    <p class="section-description">
                        دليل خطوة بخطوة لإنشاء نسخة احتياطية
                    </p>
                </div>
            </div>

            <ul class="step-list">
                <li class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h5 class="step-title">إعداد مجلد النسخ الاحتياطي</h5>
                        <p class="step-description">
                            إنشاء مجلد آمن لحفظ النسخ الاحتياطية مع ضمان عدم الوصول العام إليه
                        </p>
                    </div>
                </li>

                <li class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h5 class="step-title">نسخ قاعدة البيانات</h5>
                        <p class="step-description">
                            استخدام أدوات phpMyAdmin أو mysqldump لإنشاء نسخة من قاعدة البيانات
                        </p>
                        <div class="code-block">
mysqldump -u username -p database_name > backup.sql
                        </div>
                    </div>
                </li>

                <li class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h5 class="step-title">نسخ الملفات</h5>
                        <p class="step-description">
                            نسخ مجلد النظام والملفات المرفوعة والإعدادات المهمة
                        </p>
                        <div class="code-block">
cp -r /path/to/new_injaz /path/to/backup/folder/
                        </div>
                    </div>
                </li>

                <li class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h5 class="step-title">التحقق من النسخة</h5>
                        <p class="step-description">
                            التأكد من سلامة النسخة وإمكانية استعادتها عند الحاجة
                        </p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- تحذيرات مهمة -->
        <div class="warning-box">
            <div class="warning-header">
                <div class="warning-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h4 class="warning-title">تحذيرات مهمة</h4>
            </div>
            <ul style="margin: 0; padding-right: 2.5rem; color: #8b4513;">
                <li>لا تقم بتعديل قاعدة البيانات مباشرة دون نسخ احتياطي</li>
                <li>تأكد من تشفير كلمات المرور والمعلومات الحساسة</li>
                <li>راقب استخدام المساحة التخزينية بانتظام</li>
                <li>اختبر عملية الاستعادة قبل الاعتماد على النسخة الاحتياطية</li>
            </ul>
        </div>

        <!-- في حالة الطوارئ -->
        <div class="danger-box">
            <div class="danger-header">
                <div class="danger-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <h4 class="danger-title">في حالة الطوارئ</h4>
            </div>
            <div style="color: #721c24;">
                <p><strong>إذا حدث عطل في النظام:</strong></p>
                <ol style="padding-right: 1.5rem;">
                    <li>لا تقم بإعادة تشغيل الخادم فوراً</li>
                    <li>تحقق من سجلات الأخطاء (error logs)</li>
                    <li>استعد النسخة الاحتياطية الأخيرة</li>
                    <li>اتصل بالدعم الفني إذا لزم الأمر</li>
                </ol>
            </div>
        </div>

        <!-- نصائح للأمان -->
        <div class="success-box">
            <div class="success-header">
                <div class="success-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h4 class="success-title">نصائح للأمان</h4>
            </div>
            <div style="color: #155724;">
                <ul style="margin: 0; padding-right: 2.5rem;">
                    <li>استخدم كلمات مرور قوية ومعقدة</li>
                    <li>حدث النظام والمكتبات بانتظام</li>
                    <li>راقب الوصول إلى النظام والأنشطة المشبوهة</li>
                    <li>استخدم شهادات SSL لتشفير الاتصالات</li>
                    <li>احتفظ بنسخ احتياطية متعددة في مواقع مختلفة</li>
                </ul>
            </div>
        </div>

        <!-- معلومات إضافية -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-info me-2"></i>
                        معلومات إضافية
                    </h3>
                    <p class="section-description">
                        روابط ومراجع مفيدة للمزيد من المعلومات
                    </p>
                </div>
            </div>

            <div class="data-box">
                <div class="data-box-header">
                    <div class="data-icon">
                        <i class="bi bi-github"></i>
                    </div>
                    <h5 class="data-title">المستودع على GitHub</h5>
                </div>
                <p>يمكنك العثور على آخر التحديثات والميزات الجديدة في المستودع الرسمي للمشروع.</p>
                <a href="https://github.com/abdussalamw/new_injaz" class="btn-custom" target="_blank">
                    <i class="bi bi-github"></i>
                    زيارة المستودع
                </a>
            </div>

            <div class="data-box">
                <div class="data-box-header">
                    <div class="data-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <h5 class="data-title">خريطة الموقع</h5>
                </div>
                <p>دليل شامل لهيكل النظام وسير العمل والمسارات المهمة.</p>
                <a href="mabs.php" class="btn-custom">
                    <i class="bi bi-diagram-3"></i>
                    عرض خريطة الموقع
                </a>
            </div>

            <div class="data-box">
                <div class="data-box-header">
                    <div class="data-icon">
                        <i class="bi bi-house"></i>
                    </div>
                    <h5 class="data-title">العودة للوحة التحكم</h5>
                </div>
                <p>العودة للصفحة الرئيسية للنظام ولوحة التحكم الإدارية.</p>
                <a href="dashboard.php" class="btn-custom">
                    <i class="bi bi-house"></i>
                    لوحة التحكم
                </a>
            </div>
        </div>
    </div>

    <div class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; 2025 نظام إنجاز الإعلامية - دليل البيانات والنسخ الاحتياطي</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
