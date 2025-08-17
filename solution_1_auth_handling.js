// ========================================
// الحل الأول: معالجة أخطاء المصادقة بذكاء
// ========================================

// استبدال منطق fetch في form.php
fetch(`${apiPath}?query=${encodeURIComponent(query)}`)
    .then(response => {
        // التحقق من إعادة التوجيه للتسجيل
        if (response.url && response.url.includes('/login')) {
            throw new Error('AUTHENTICATION_REQUIRED');
        }
        
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Server returned non-JSON response');
            });
        }
        
        return response.json();
    })
    .then(clients => {
        // نفس المنطق الموجود
    })
    .catch(error => {
        if (error.message === 'AUTHENTICATION_REQUIRED') {
            autocompleteList.innerHTML = `
                <div class="list-group-item text-center py-3 text-warning">
                    <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-1">انتهت جلسة العمل</p>
                    <small>الرجاء <a href="${window.location.origin}<?= $_ENV['BASE_PATH'] ?>/login">تسجيل الدخول</a> مرة أخرى</small>
                </div>
            `;
        } else {
            // نفس معالجة الخطأ الموجودة
        }
    });
