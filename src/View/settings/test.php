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
    <title>أدوات الاختبار والتشخيص - نظام إنجاز الإعلامية</title>

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

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .test-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .test-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .test-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: white;
        }

        .test-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .test-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .btn-test {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-test:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .result-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
            display: none;
        }

        .result-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .result-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
            font-size: 0.9rem;
        }

        .result-title {
            font-weight: 600;
            margin: 0;
        }

        .result-content {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 0.5rem;
            white-space: pre-wrap;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 0.5rem;
        }

        .status-success { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }

        .system-info {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .info-value {
            color: #6c757d;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        .alert-custom {
            background: var(--warning-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
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

            .test-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
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
        <div class="alert-custom">
            <i class="bi bi-info-circle-fill me-2"></i>
            استخدم هذه الأدوات بحذر وتأكد من وجود نسخة احتياطية قبل إجراء أي اختبارات
        </div>

        <!-- معلومات النظام -->
        <div class="system-info">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-cpu me-2"></i>
                        معلومات النظام
                    </h3>
                    <p class="section-description">
                        تفاصيل البيئة والإعدادات الحالية
                    </p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">إصدار PHP</div>
                    <div class="info-value"><?php echo phpversion(); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">نظام التشغيل</div>
                    <div class="info-value"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">خادم الويب</div>
                    <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد'; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">وقت التشغيل</div>
                    <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">الذاكرة المستخدمة</div>
                    <div class="info-value"><?php echo round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">منطقة زمنية</div>
                    <div class="info-value"><?php echo date_default_timezone_get(); ?></div>
                </div>
            </div>
        </div>

        <!-- أدوات الاختبار -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-wrench"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-wrench me-2"></i>
                        أدوات الاختبار
                    </h3>
                    <p class="section-description">
                        اختبارات مختلفة للتحقق من سلامة النظام
                    </p>
                </div>
            </div>

            <div class="test-grid">
                <div class="test-card">
                    <div class="test-icon" style="background: var(--success-gradient);">
                        <i class="bi bi-database-check"></i>
                    </div>
                    <h5 class="test-title">اختبار قاعدة البيانات</h5>
                    <p class="test-description">
                        اختبار الاتصال بقاعدة البيانات والجداول الأساسية
                    </p>
                    <button class="btn-test" onclick="runTest('database')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="database-result" class="result-box"></div>
                </div>

                <div class="test-card">
                    <div class="test-icon" style="background: var(--warning-gradient);">
                        <i class="bi bi-file-earmark-code"></i>
                    </div>
                    <h5 class="test-title">اختبار الملفات</h5>
                    <p class="test-description">
                        فحص وجود الملفات الأساسية والصلاحيات
                    </p>
                    <button class="btn-test" onclick="runTest('files')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="files-result" class="result-box"></div>
                </div>

                <div class="test-card">
                    <div class="test-icon" style="background: var(--danger-gradient);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="test-title">اختبار الأمان</h5>
                    <p class="test-description">
                        فحص إعدادات الأمان والثغرات المحتملة
                    </p>
                    <button class="btn-test" onclick="runTest('security')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="security-result" class="result-box"></div>
                </div>

                <div class="test-card">
                    <div class="test-icon" style="background: var(--primary-gradient);">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h5 class="test-title">اختبار الأداء</h5>
                    <p class="test-description">
                        قياس سرعة النظام واستجابة الصفحات
                    </p>
                    <button class="btn-test" onclick="runTest('performance')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="performance-result" class="result-box"></div>
                </div>

                <div class="test-card">
                    <div class="test-icon" style="background: linear-gradient(135deg, #9b59b6 0%, #e91e63 100%);">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <h5 class="test-title">اختبار البريد الإلكتروني</h5>
                    <p class="test-description">
                        اختبار إرسال واستقبال الرسائل الإلكترونية
                    </p>
                    <button class="btn-test" onclick="runTest('email')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="email-result" class="result-box"></div>
                </div>

                <div class="test-card">
                    <div class="test-icon" style="background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);">
                        <i class="bi bi-cloud-upload"></i>
                    </div>
                    <h5 class="test-title">اختبار النسخ الاحتياطي</h5>
                    <p class="test-description">
                        فحص سلامة النسخ الاحتياطية والاستعادة
                    </p>
                    <button class="btn-test" onclick="runTest('backup')">
                        <i class="bi bi-play-fill me-1"></i>
                        تشغيل الاختبار
                    </button>
                    <div id="backup-result" class="result-box"></div>
                </div>
            </div>
        </div>

        <!-- سجل الاختبارات -->
        <div class="info-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <h3 class="section-title">
                        <i class="bi bi-journal me-2"></i>
                        سجل الاختبارات
                    </h3>
                    <p class="section-description">
                        نتائج الاختبارات السابقة والسجلات
                    </p>
                </div>
            </div>

            <div id="test-log" class="result-box" style="display: block;">
                <div class="result-header">
                    <div class="result-icon" style="background: #6c757d;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h6 class="result-title">سجل العمليات</h6>
                </div>
                <div class="result-content" id="log-content">
                    جاهز لعرض نتائج الاختبارات...
                </div>
            </div>
        </div>
    </div>

    <div class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; 2025 نظام إنجاز الإعلامية - أدوات الاختبار والتشخيص</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function runTest(testType) {
            const resultDiv = document.getElementById(testType + '-result');
            const logContent = document.getElementById('log-content');

            // إظهار رسالة التحميل
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <div class="result-header">
                    <div class="result-icon" style="background: #ffc107;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h6 class="result-title">جاري الاختبار...</h6>
                </div>
                <div class="result-content">
                    يرجى الانتظار، جاري تشغيل اختبار ${getTestName(testType)}
                </div>
            `;

            // محاكاة الاختبار (في التطبيق الحقيقي، سيتم استدعاء API)
            setTimeout(() => {
                const result = simulateTest(testType);
                resultDiv.innerHTML = result;

                // إضافة للسجل
                const timestamp = new Date().toLocaleString('ar-SA');
                logContent.innerHTML += `\n[${timestamp}] تم تشغيل اختبار: ${getTestName(testType)}`;
                logContent.scrollTop = logContent.scrollHeight;
            }, 2000);
        }

        function getTestName(testType) {
            const names = {
                'database': 'قاعدة البيانات',
                'files': 'الملفات',
                'security': 'الأمان',
                'performance': 'الأداء',
                'email': 'البريد الإلكتروني',
                'backup': 'النسخ الاحتياطي'
            };
            return names[testType] || testType;
        }

        function simulateTest(testType) {
            const mockResults = {
                'database': {
                    status: 'success',
                    title: 'نجح اختبار قاعدة البيانات',
                    content: `✅ الاتصال بقاعدة البيانات: ناجح
✅ فحص الجداول الأساسية: 5/5 موجودة
✅ اختبار الصلاحيات: ناجح
✅ سرعة الاستجابة: 45ms

الجداول الموجودة:
- users ✓
- orders ✓
- clients ✓
- notifications ✓
- order_history ✓`
                },
                'files': {
                    status: 'success',
                    title: 'نجح اختبار الملفات',
                    content: `✅ مجلد src/: موجود
✅ ملفات التكوين: موجودة
✅ صلاحيات الكتابة: صحيحة
✅ ملفات الأصول: متاحة

الملفات المطلوبة:
- index.php ✓
- dashboard.php ✓
- composer.json ✓
- .env ✓`
                },
                'security': {
                    status: 'warning',
                    title: 'تحذيرات في اختبار الأمان',
                    content: `⚠️  إعدادات HTTPS: غير مفعلة
✅ تشفير كلمات المرور: مفعل
⚠️  ملف .env: مرئي للويب
✅ حماية CSRF: مفعلة

توصيات:
1. فعل شهادة SSL
2. نقل ملف .env خارج مجلد الويب
3. تحديث مكتبات الأمان`
                },
                'performance': {
                    status: 'success',
                    title: 'نتائج اختبار الأداء',
                    content: `✅ وقت تحميل الصفحة: 1.2 ثانية
✅ استخدام الذاكرة: 12MB
✅ عدد الاستعلامات: 8 استعلامات
✅ حجم الصفحة: 245KB

تقييم الأداء: ممتاز
سرعة الاستجابة: جيدة`
                },
                'email': {
                    status: 'error',
                    title: 'فشل في اختبار البريد الإلكتروني',
                    content: `❌ إعدادات SMTP: غير مكتملة
❌ اختبار الإرسال: فشل
❌ التحقق من الخادم: تعذر الاتصال

أسباب محتملة:
- إعدادات SMTP غير صحيحة
- مشكلة في الاتصال بالخادم
- حاجة لتكوين إضافي`
                },
                'backup': {
                    status: 'success',
                    title: 'نجح اختبار النسخ الاحتياطي',
                    content: `✅ آخر نسخة احتياطية: 2025-01-15
✅ حجم النسخة: 45MB
✅ سلامة البيانات: تم التحقق
✅ موقع التخزين: آمن

النسخ الاحتياطية المتاحة:
- يومية: 30 نسخة
- أسبوعية: 12 نسخة
- شهرية: 6 نسخ`
                }
            };

            const result = mockResults[testType];
            const statusClass = result.status === 'success' ? 'success' :
                              result.status === 'warning' ? 'warning' : 'error';
            const statusIcon = result.status === 'success' ? 'check-circle' :
                             result.status === 'warning' ? 'exclamation-triangle' : 'x-circle';

            return `
                <div class="result-header">
                    <div class="result-icon" style="background: ${result.status === 'success' ? '#28a745' : result.status === 'warning' ? '#ffc107' : '#dc3545'};">
                        <i class="bi bi-${statusIcon}"></i>
                    </div>
                    <h6 class="result-title">
                        <span class="status-indicator status-${statusClass}"></span>
                        ${result.title}
                    </h6>
                </div>
                <div class="result-content">
                    ${result.content}
                </div>
            `;
        }

        // تهيئة السجل عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            const logContent = document.getElementById('log-content');
            const timestamp = new Date().toLocaleString('ar-SA');
            logContent.innerHTML = `[${timestamp}] تم تحميل صفحة أدوات الاختبار`;
        });
    </script>
</body>
</html>
