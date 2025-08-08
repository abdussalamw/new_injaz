// إدارة الشريط الجانبي الديناميكي
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const mainContent = document.querySelector('.main-content');

    // إنشاء زر التحكم إذا لم يكن موجوداً
    if (!toggleBtn && sidebar) {
        const btn = document.createElement('button');
        btn.className = 'sidebar-toggle';
        btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        btn.setAttribute('title', 'تصغير/توسيع القائمة');
        sidebar.appendChild(btn);

        // إضافة مستمع الأحداث للزر الجديد
        btn.addEventListener('click', toggleSidebar);
    } else if (toggleBtn) {
        // إضافة مستمع الأحداث للزر الموجود
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    // وظيفة تبديل حالة الشريط الجانبي
    function toggleSidebar() {
        const isCollapsed = sidebar.classList.contains('collapsed');
        const btn = document.querySelector('.sidebar-toggle');

        if (isCollapsed) {
            // توسيع الشريط الجانبي
            sidebar.classList.remove('collapsed');
            if (mainContent) {
                mainContent.classList.remove('expanded');
            }
            if (btn) {
                btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                btn.setAttribute('title', 'تصغير القائمة');
            }
            // حفظ الحالة
            localStorage.setItem('sidebarCollapsed', 'false');
        } else {
            // تصغير الشريط الجانبي
            sidebar.classList.add('collapsed');
            if (mainContent) {
                mainContent.classList.add('expanded');
            }
            if (btn) {
                btn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                btn.setAttribute('title', 'توسيع القائمة');
            }
            // حفظ الحالة
            localStorage.setItem('sidebarCollapsed', 'true');
        }
    }

    // استعادة حالة الشريط الجانبي من التخزين المحلي
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
        // تحديث أيقونة الزر فوراً عند التحميل
        const btn = document.querySelector('.sidebar-toggle');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            btn.setAttribute('title', 'توسيع القائمة');
        }
    } else if (savedState === 'false' && sidebar) {
        // التأكد من إزالة فئة collapsed إذا كانت الحالة false
        sidebar.classList.remove('collapsed');
        if (mainContent) {
            mainContent.classList.remove('expanded');
        }
        const btn = document.querySelector('.sidebar-toggle');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            btn.setAttribute('title', 'تصغير القائمة');
        }
    }

    // تحسين التجاوب للشاشات الصغيرة
    function handleResponsive() {
        if (window.innerWidth < 768) {
            if (sidebar && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                if (mainContent) {
                    mainContent.classList.add('expanded');
                }
            }
        } else {
            // عند العودة لشاشة كبيرة، استعد الحالة المحفوظة
            const savedStateOnResize = localStorage.getItem('sidebarCollapsed');
            if (savedStateOnResize === 'false' && sidebar) {
                sidebar.classList.remove('collapsed');
                if (mainContent) {
                    mainContent.classList.remove('expanded');
                }
                const btn = document.querySelector('.sidebar-toggle');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                    btn.setAttribute('title', 'تصغير القائمة');
                }
            }
        }
    }

    // تطبيق التجاوب عند تحميل الصفحة وتغيير حجم النافذة
    handleResponsive();
    window.addEventListener('resize', handleResponsive);

    // إضافة تأثيرات hover للعناصر
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            if (sidebar && sidebar.classList.contains('collapsed')) {
                // إظهار tooltip عند التمرير على العنصر في الوضع المصغر
                const text = this.querySelector('span');
                if (text) {
                    this.setAttribute('title', text.textContent.trim());
                }
            }
        });
    });
});

// وظائف إضافية لتحسين تجربة المستخدم
function initSidebarAnimations() {
    const sidebar = document.querySelector('.sidebar');
    const navLinks = document.querySelectorAll('.sidebar .nav-link');

    if (!sidebar) return;

    // تأثير النقر على الروابط
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // إزالة الحالة النشطة من جميع الروابط
            navLinks.forEach(l => l.classList.remove('active'));
            // إضافة الحالة النشطة للرابط المنقور
            this.classList.add('active');

            // حفظ الرابط النشط
            const href = this.getAttribute('href');
            if (href) {
                localStorage.setItem('activeNavLink', href);
            }
        });
    });

    // استعادة الرابط النشط
    const activeLink = localStorage.getItem('activeNavLink');
    if (activeLink) {
        const link = document.querySelector(`[href="${activeLink}"]`);
        if (link) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    }
}

// تهيئة الرسوم المتحركة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', initSidebarAnimations);