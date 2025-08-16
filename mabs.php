<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خريطة الموقع وسير الأوامر - نظام إنجاز الإعلامية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            font-family: 'Tajawal', Arial, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .main-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .section-title {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .flow-step {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .file-structure {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .folder { color: #f39c12; }
        .file { color: #2ecc71; }
        .important { color: #e74c3c; font-weight: bold; }
        .route-box {
            background: #ecf0f1;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #9b59b6;
        }
        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin: 2px;
        }
        .admin { background: #e74c3c; color: white; }
        .manager { background: #f39c12; color: white; }
        .designer { background: #3498db; color: white; }
        .accountant { background: #2ecc71; color: white; }
        .workshop { background: #9b59b6; color: white; }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <h1><i class="bi bi-diagram-3"></i> خريطة الموقع وسير الأوامر</h1>
            <p class="lead">دليل شامل لفهم نظام إنجاز الإعلامية</p>
        </div>
    </div>

    <div class="container">
        <!-- هيكل المشروع -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-folder-fill"></i> هيكل المشروع</h2>
                <div class="file-structure">
new_injaz/
├── <span class="folder">📁 public/</span>                 # الملفات العامة والأصول
│   ├── <span class="file">index.php</span>               # نقطة الدخول الرئيسية
│   └── <span class="folder">📁 assets/</span>            # الصور والملفات الثابتة
├── <span class="folder">📁 src/</span>                    # الكود المصدري الرئيسي
│   ├── <span class="folder">📁 Controller/</span>        # تحكم في العمليات
│   ├── <span class="folder">📁 View/</span>              # واجهات المستخدم
│   ├── <span class="folder">📁 Core/</span>              # الوظائف الأساسية
│   ├── <span class="folder">📁 Api/</span>               # واجهات البرمجة
│   ├── <span class="folder">📁 Auth/</span>              # المصادقة والتسجيل
│   └── <span class="folder">📁 Reports/</span>           # التقارير والإحصائيات
├── <span class="important">📄 .env</span>                       # إعدادات البيئة (مهم جداً!)
├── <span class="file">composer.json</span>              # إعدادات Composer
├── <span class="file">dashboard.php</span>              # لوحة التحكم الرئيسية
└── <span class="file">index.php</span>                  # نقطة الدخول مع التوجيه
                </div>
            </div>
        </div>

        <!-- سير العمل في النظام -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-arrow-right-circle"></i> سير العمل في النظام</h2>
                
                <div class="flow-step">
                    <h4><i class="bi bi-1-circle-fill text-primary"></i> إنشاء الطلب</h4>
                    <p><strong>المسؤول:</strong> <span class="role-badge admin">مدير</span></p>
                    <p>يتم إنشاء طلب جديد من خلال النموذج، ويُحفظ في قاعدة البيانات مع تخصيص مصمم.</p>
                    <div class="route-box">
                        <strong>المسار:</strong> /orders/add → OrderController::add()
                    </div>
                </div>

                <div class="flow-step">
                    <h4><i class="bi bi-2-circle-fill text-info"></i> مرحلة التصميم</h4>
                    <p><strong>المسؤول:</strong> <span class="role-badge designer">مصمم</span></p>
                    <p>المصمم يعمل على الطلب ويُحدث حالته إلى "قيد التصميم" ثم "جاهز للتنفيذ".</p>
                    <div class="route-box">
                        <strong>التحديث:</strong> API endpoint → ApiController::changeOrderStatus()
                    </div>
                </div>

                <div class="flow-step">
                    <h4><i class="bi bi-3-circle-fill text-warning"></i> مرحلة التنفيذ</h4>
                    <p><strong>المسؤول:</strong> <span class="role-badge workshop">معمل</span></p>
                    <p>المعمل ينفذ التصميم ويُحدث الحالة إلى "قيد التنفيذ" ثم "جاهز للتسليم".</p>
                </div>

                <div class="flow-step">
                    <h4><i class="bi bi-4-circle-fill text-success"></i> التسليم والدفع</h4>
                    <p><strong>المسؤول:</strong> <span class="role-badge manager">مدير</span> <span class="role-badge accountant">محاسب</span></p>
                    <p>يتم تسليم الطلب للعميل وتحديث حالة الدفع، مع إرسال إشعارات تلقائية.</p>
                </div>
            </div>
        </div>

        <!-- نظام الصلاحيات -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-shield-check"></i> نظام الصلاحيات</h2>
                <div class="row">
                    <div class="col-md-6">
                        <h5><span class="role-badge admin">مدير</span></h5>
                        <ul>
                            <li>إضافة وتعديل جميع البيانات</li>
                            <li>عرض جميع التقارير والإحصائيات</li>
                            <li>إدارة صلاحيات الموظفين</li>
                            <li>تأكيد التسليم والدفع</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><span class="role-badge designer">مصمم</span></h5>
                        <ul>
                            <li>عرض الطلبات المخصصة له فقط</li>
                            <li>تحديث حالة الطلبات الخاصة به</li>
                            <li>رفع الملفات والتصاميم</li>
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5><span class="role-badge accountant">محاسب</span></h5>
                        <ul>
                            <li>تحديث حالة الدفع</li>
                            <li>عرض التقارير المالية</li>
                            <li>إدارة المدفوعات</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><span class="role-badge workshop">معمل</span></h5>
                        <ul>
                            <li>عرض الطلبات المخصصة له</li>
                            <li>تحديث حالة التنفيذ</li>
                            <li>تقييم المراحل</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- المسارات والروابط المهمة -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-signpost"></i> المسارات المهمة</h2>
                <div class="row">
                    <div class="col-md-6">
                        <h5>مسارات المستخدم:</h5>
                        <div class="route-box">/ → لوحة التحكم الرئيسية</div>
                        <div class="route-box">/login → صفحة تسجيل الدخول</div>
                        <div class="route-box">/orders → إدارة الطلبات</div>
                        <div class="route-box">/clients → إدارة العملاء</div>
                        <div class="route-box">/reports → التقارير والإحصائيات</div>
                    </div>
                    <div class="col-md-6">
                        <h5>مسارات API:</h5>
                        <div class="route-box">/api/tasks → تصفية المهام</div>
                        <div class="route-box">/api/orders/status → تحديث حالة الطلب</div>
                        <div class="route-box">/api/orders/payment → تحديث الدفع</div>
                        <div class="route-box">/api/clients/search → البحث في العملاء</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- نظام الإشعارات -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-bell"></i> نظام الإشعارات</h2>
                <div class="flow-step">
                    <h5>إشعارات تغيير الحالة:</h5>
                    <p>عند تغيير حالة أي طلب، يتم إرسال إشعار للمدراء والموظفين المسؤولين.</p>
                </div>
                <div class="flow-step">
                    <h5>إشعارات الدفع:</h5>
                    <p>عند تحديث حالة الدفع، يتم إشعار المدراء بالتغيير.</p>
                </div>
                <div class="flow-step">
                    <h5>آلية العمل:</h5>
                    <p>الإشعارات تُحفظ في جدول <code>notifications</code> وتظهر في الهيدر للمستخدمين المستهدفين.</p>
                </div>
            </div>
        </div>

        <!-- روابط مهمة -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-link-45deg"></i> روابط مفيدة</h2>
                <div class="row">
                    <div class="col-md-6">
                        <a href="dashboard.php" class="btn btn-primary mb-2 w-100">
                            <i class="bi bi-speedometer2"></i> لوحة التحكم
                        </a>
                        <a href="data-guide.php" class="btn btn-info mb-2 w-100">
                            <i class="bi bi-database"></i> دليل البيانات
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="https://github.com/abdussalamw/new_injaz" class="btn btn-dark mb-2 w-100" target="_blank">
                            <i class="bi bi-github"></i> المشروع على GitHub
                        </a>
                        <a href="index.php" class="btn btn-success mb-2 w-100">
                            <i class="bi bi-house"></i> الصفحة الرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 mt-5" style="background: #2c3e50; color: white;">
        <div class="container">
            <p class="mb-0">&copy; 2025 نظام إنجاز الإعلامية - خريطة الموقع وسير الأوامر</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
