<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل البيانات والنسخ الاحتياطي - نظام إنجاز الإعلامية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            font-family: 'Tajawal', Arial, sans-serif; 
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
        }
        .main-header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
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
            color: #1565c0;
            border-bottom: 3px solid #42a5f5;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .data-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #42a5f5;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #f39c12;
        }
        .danger-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #dc3545;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #28a745;
        }
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
        }
        .table-name {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin: 5px;
            font-size: 14px;
        }
        .file-type {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            display: inline-block;
            margin: 2px;
            font-size: 12px;
        }
        .backup-step {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #27ae60;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .frequency {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin: 3px;
        }
        .daily { background: #e74c3c; color: white; }
        .weekly { background: #f39c12; color: white; }
        .monthly { background: #3498db; color: white; }
        .critical { background: #2c3e50; color: white; }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <h1><i class="bi bi-database"></i> دليل البيانات والنسخ الاحتياطي</h1>
            <p class="lead">دليل شامل لفهم كيفية حفظ وإدارة البيانات في النظام</p>
        </div>
    </div>

    <div class="container">
        <!-- البيانات المحفوظة في قاعدة البيانات -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-server"></i> البيانات المحفوظة في قاعدة البيانات MySQL</h2>
                
                <div class="danger-box">
                    <h5><i class="bi bi-exclamation-triangle"></i> تحذير مهم!</h5>
                    <p><strong>هذه البيانات حيوية ويجب نسخها احتياطياً بشكل دوري!</strong></p>
                    <p>فقدان قاعدة البيانات يعني فقدان جميع بيانات النظام.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5>الجداول الأساسية:</h5>
                        <span class="table-name">orders</span> - الطلبات والمشاريع
                        <span class="table-name">clients</span> - بيانات العملاء
                        <span class="table-name">employees</span> - بيانات الموظفين
                        <span class="table-name">products</span> - المنتجات والخدمات
                        <span class="table-name">tasks</span> - المهام اليومية
                    </div>
                    <div class="col-md-6">
                        <h5>الجداول المساعدة:</h5>
                        <span class="table-name">notifications</span> - الإشعارات
                        <span class="table-name">user_sessions</span> - جلسات المستخدمين
                        <span class="table-name">order_history</span> - تاريخ الطلبات
                        <span class="table-name">employee_permissions</span> - الصلاحيات
                        <span class="table-name">system_settings</span> - إعدادات النظام
                    </div>
                </div>

                <div class="data-box">
                    <h5>تفاصيل الجداول المهمة:</h5>
                    <ul>
                        <li><strong>orders:</strong> معلومات الطلبات، التسعير، الحالة، تواريخ التسليم</li>
                        <li><strong>clients:</strong> أسماء العملاء، معلومات الاتصال، العناوين</li>
                        <li><strong>employees:</strong> بيانات الموظفين، الأدوار، كلمات المرور</li>
                        <li><strong>tasks:</strong> المهام اليومية، الموظف المخصص، التقييمات</li>
                        <li><strong>notifications:</strong> الإشعارات، تواريخ الإرسال، حالة القراءة</li>
                    </ul>
                </div>

                <div class="code-block">
# أمر نسخ قاعدة البيانات احتياطياً
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# أمر استعادة قاعدة البيانات
mysql -u username -p database_name < backup_file.sql
                </div>
            </div>
        </div>

        <!-- الملفات المحفوظة في نظام الملفات -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-folder"></i> الملفات المحفوظة في نظام الملفات</h2>

                <div class="data-box">
                    <h5><i class="bi bi-file-earmark-text"></i> ملفات الإعدادات:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><span class="file-type">.env</span> إعدادات قاعدة البيانات</li>
                                <li><span class="file-type">config.php</span> إعدادات النظام</li>
                                <li><span class="file-type">composer.json</span> مكتبات PHP</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><span class="file-type">routes.php</span> مسارات النظام</li>
                                <li><span class="file-type">.htaccess</span> إعدادات الخادم</li>
                                <li><span class="file-type">database.sql</span> هيكل قاعدة البيانات</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <h5><i class="bi bi-exclamation-circle"></i> ملفات حساسة يجب حمايتها:</h5>
                    <ul>
                        <li><strong>.env</strong> - يحتوي على كلمات مرور قاعدة البيانات</li>
                        <li><strong>src/Core/Database.php</strong> - إعدادات الاتصال</li>
                        <li><strong>أي ملف يحتوي على مفاتيح API أو كلمات مرور</strong></li>
                    </ul>
                </div>

                <div class="data-box">
                    <h5><i class="bi bi-images"></i> ملفات الوسائط (إذا وجدت):</h5>
                    <ul>
                        <li><strong>uploads/</strong> - ملفات المرفوعة من المستخدمين</li>
                        <li><strong>assets/images/</strong> - صور المنتجات والعملاء</li>
                        <li><strong>documents/</strong> - مستندات وملفات PDF</li>
                        <li><strong>backups/</strong> - النسخ الاحتياطية المحلية</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- البيانات المحفوظة في المتصفح -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-browser-chrome"></i> البيانات المحفوظة في المتصفح</h2>

                <div class="data-box">
                    <h5><i class="bi bi-cookie"></i> البيانات المؤقتة:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><strong>Cookies:</strong> معلومات تسجيل الدخول</li>
                                <li><strong>Session Storage:</strong> بيانات الجلسة المؤقتة</li>
                                <li><strong>Local Storage:</strong> إعدادات المستخدم</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><strong>Cache:</strong> ملفات CSS/JS المخزنة مؤقتاً</li>
                                <li><strong>Service Worker:</strong> بيانات العمل دون إنترنت</li>
                                <li><strong>IndexedDB:</strong> بيانات محلية متقدمة</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="success-box">
                    <h5><i class="bi bi-info-circle"></i> معلومة مهمة:</h5>
                    <p>البيانات في المتصفح مؤقتة ولا تؤثر على النظام الأساسي. يمكن مسحها دون خوف.</p>
                </div>
            </div>
        </div>

        <!-- استراتيجية النسخ الاحتياطي -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-shield-check"></i> استراتيجية النسخ الاحتياطي الموصى بها</h2>

                <div class="backup-step">
                    <h4><i class="bi bi-1-circle-fill text-primary"></i> النسخ الاحتياطي لقاعدة البيانات</h4>
                    <p><span class="frequency daily">يومي</span> <span class="frequency critical">حرج جداً</span></p>
                    <div class="code-block">
# إنشاء نسخة احتياطية تلقائية يومية
0 2 * * * mysqldump -u username -p database_name > /path/to/backups/daily_backup_$(date +\%Y\%m\%d).sql
                    </div>
                </div>

                <div class="backup-step">
                    <h4><i class="bi bi-2-circle-fill text-info"></i> نسخ ملفات النظام</h4>
                    <p><span class="frequency weekly">أسبوعي</span></p>
                    <div class="code-block">
# نسخ جميع ملفات المشروع
tar -czf project_backup_$(date +%Y%m%d).tar.gz /path/to/new_injaz/

# نسخ الملفات المهمة فقط
cp -r src/ config/ .env backups/important_files_$(date +%Y%m%d)/
                    </div>
                </div>

                <div class="backup-step">
                    <h4><i class="bi bi-3-circle-fill text-warning"></i> النسخ الاحتياطي السحابي</h4>
                    <p><span class="frequency weekly">أسبوعي</span> <span class="frequency critical">مهم جداً</span></p>
                    <ul>
                        <li>رفع النسخ الاحتياطية إلى Google Drive أو Dropbox</li>
                        <li>استخدام خدمات النسخ الاحتياطي التلقائي</li>
                        <li>التأكد من تشفير الملفات الحساسة</li>
                    </ul>
                </div>

                <div class="backup-step">
                    <h4><i class="bi bi-4-circle-fill text-success"></i> النسخ الاحتياطي الشامل</h4>
                    <p><span class="frequency monthly">شهري</span></p>
                    <ul>
                        <li>نسخة كاملة من قاعدة البيانات</li>
                        <li>نسخة كاملة من جميع ملفات المشروع</li>
                        <li>نسخة من إعدادات الخادم</li>
                        <li>اختبار استعادة النسخة الاحتياطية</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- قائمة التحقق للنسخ الاحتياطي -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-check2-square"></i> قائمة التحقق الشهرية</h2>

                <div class="row">
                    <div class="col-md-6">
                        <h5>ما يجب فعله:</h5>
                        <div class="success-box">
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check-circle text-success"></i> فحص سلامة النسخ الاحتياطية</li>
                                <li><i class="bi bi-check-circle text-success"></i> اختبار استعادة نسخة احتياطية</li>
                                <li><i class="bi bi-check-circle text-success"></i> تنظيف النسخ القديمة (أكثر من 3 أشهر)</li>
                                <li><i class="bi bi-check-circle text-success"></i> تحديث أمان كلمات المرور</li>
                                <li><i class="bi bi-check-circle text-success"></i> فحص مساحة التخزين المتاحة</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>ما يجب تجنبه:</h5>
                        <div class="danger-box">
                            <ul class="list-unstyled">
                                <li><i class="bi bi-x-circle text-danger"></i> الاعتماد على نسخة احتياطية واحدة فقط</li>
                                <li><i class="bi bi-x-circle text-danger"></i> تخزين النسخ الاحتياطية في نفس الخادم</li>
                                <li><i class="bi bi-x-circle text-danger"></i> إهمال اختبار استعادة النسخ</li>
                                <li><i class="bi bi-x-circle text-danger"></i> تجاهل تحديث كلمات المرور</li>
                                <li><i class="bi bi-x-circle text-danger"></i> نسخ الملفات الحساسة دون تشفير</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أماكن التخزين الموصى بها -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-cloud"></i> أماكن التخزين الموصى بها</h2>

                <div class="row">
                    <div class="col-md-4">
                        <div class="data-box text-center">
                            <i class="bi bi-cloud-upload text-primary" style="font-size: 3rem;"></i>
                            <h5>التخزين السحابي</h5>
                            <p>Google Drive, Dropbox, OneDrive</p>
                            <span class="frequency critical">أولوية عالية</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="data-box text-center">
                            <i class="bi bi-hdd text-warning" style="font-size: 3rem;"></i>
                            <h5>القرص الصلب الخارجي</h5>
                            <p>نسخ احتياطية محلية آمنة</p>
                            <span class="frequency weekly">أسبوعي</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="data-box text-center">
                            <i class="bi bi-server text-info" style="font-size: 3rem;"></i>
                            <h5>خادم منفصل</h5>
                            <p>نسخ احتياطية على خادم آخر</p>
                            <span class="frequency monthly">شهري</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أوامر سريعة للطوارئ -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-lightning"></i> أوامر سريعة للطوارئ</h2>

                <div class="danger-box">
                    <h5><i class="bi bi-exclamation-triangle"></i> في حالة الطوارئ - نسخة سريعة:</h5>
                    <div class="code-block">
# نسخة سريعة لقاعدة البيانات
mysqldump -u username -p database_name > emergency_backup.sql

# نسخة سريعة للملفات المهمة
tar -czf emergency_files.tar.gz src/ .env composer.json

# رفع سريع للسحابة (إذا كان متاح)
scp emergency_backup.sql user@backup-server:/backups/
                    </div>
                </div>

                <div class="success-box">
                    <h5><i class="bi bi-info-circle"></i> استعادة سريعة:</h5>
                    <div class="code-block">
# استعادة قاعدة البيانات
mysql -u username -p database_name < backup_file.sql

# استعادة الملفات
tar -xzf backup_files.tar.gz

# إعادة تشغيل الخدمات
sudo systemctl restart apache2
sudo systemctl restart mysql
                    </div>
                </div>
            </div>
        </div>

        <!-- روابط مهمة -->
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-link-45deg"></i> روابط مفيدة</h2>
                <div class="row">
                    <div class="col-md-6">
                        <a href="mabs.php" class="btn btn-primary mb-2 w-100">
                            <i class="bi bi-diagram-3"></i> خريطة الموقع
                        </a>
                        <a href="dashboard.php" class="btn btn-success mb-2 w-100">
                            <i class="bi bi-speedometer2"></i> لوحة التحكم
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="https://github.com/abdussalamw/new_injaz" class="btn btn-dark mb-2 w-100" target="_blank">
                            <i class="bi bi-github"></i> المشروع على GitHub
                        </a>
                        <a href="index.php" class="btn btn-info mb-2 w-100">
                            <i class="bi bi-house"></i> الصفحة الرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 mt-5" style="background: #1565c0; color: white;">
        <div class="container">
            <p class="mb-0">&copy; 2025 نظام إنجاز الإعلامية - دليل البيانات والنسخ الاحتياطي</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
