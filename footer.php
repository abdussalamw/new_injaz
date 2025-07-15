    </main>
  </div>
</div>
<footer class="text-center py-4 mt-5" style="background:#fff;color:#D44759;">
    &copy; <?= date('Y') ?> إنجاز الإعلامية — جميع الحقوق محفوظة.
</footer>
<!-- Chart.js for creating charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Bootstrap JS (ضروري لبعض مكونات الواجهة مثل النوافذ المنبثقة والقوائم المنسدلة) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.4/dist/js/bootstrap.bundle.min.js"></script>

<!-- نافذة تأكيد الإجراء العامة -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmationModalLabel">تأكيد الإجراء</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="confirmationModalBody">
        هل أنت متأكد من رغبتك في المتابعة؟
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-primary" id="confirmActionBtn">تأكيد</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownElements = document.querySelectorAll('.countdown');

    // دالة موحدة لتحديث جميع أنواع العدادات
    function updateCountdown() {
        countdownElements.forEach(el => {
            const now = new Date();
            let diff, prefix, isExpired = false;

            if (el.dataset.orderDate) {
                const orderDate = new Date(el.dataset.orderDate);
                diff = now - orderDate;
                prefix = 'منذ ';
            } else if (el.dataset.dueDate) {
                const dueDate = new Date(el.dataset.dueDate + 'T23:59:59');
                const diff = dueDate - now;
                prefix = 'متبقي: ';
                if (diff <= 0) isExpired = true;
            } else {
                return; // لا يوجد عداد ليعمل
            }

            if (isExpired) {
                el.innerHTML = '<span class="text-danger fw-bold">انتهى الوقت!</span>';
                return;
            }

            // حساب الأيام والساعات والدقائق والثواني
            const absoluteDiff = Math.abs(diff);
            const days = Math.floor(absoluteDiff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((absoluteDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((absoluteDiff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((absoluteDiff % (1000 * 60)) / 1000);

            // تنسيق الوقت لعرض الأيام، الساعات، الدقائق، والثواني بشكل مضغوط ومفصل
            let parts = [];
            if (days > 0) {
                parts.push(`<span class="fw-bold fs-5">${days}</span>ي`);
            }
            // إضافة الساعات والدقائق والثواني دائماً
            parts.push(`<span class="fw-bold fs-5">${String(hours).padStart(2, '0')}</span>س`);
            parts.push(`<span class="fw-bold fs-5">${String(minutes).padStart(2, '0')}</span>د`);
            parts.push(`<span class="fw-bold fs-5">${String(seconds).padStart(2, '0')}</span>ث`);

            el.innerHTML = prefix + parts.join(' : ');
        });
    }

    // تشغيل العداد إذا وجدت عناصر للعد التنازلي
    if (countdownElements.length > 0) {
        updateCountdown(); // التشغيل أول مرة فوراً
        setInterval(updateCountdown, 1000); // تحديث كل ثانية
    }
});

// --- Global Action Button Handler ---
document.addEventListener('DOMContentLoaded', function () {
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmActionBtn = document.getElementById('confirmActionBtn');
    const confirmationModalBody = document.getElementById('confirmationModalBody');
    let currentActionData = {};

    // استخدام body للاستماع للأحداث لضمان عمله في كل الصفحات
    document.body.addEventListener('click', function (e) {
        // البحث عن أقرب زر يحمل الكلاس المطلوب
        const actionButton = e.target.closest('.action-btn');
        if (actionButton) {
            e.preventDefault();
            currentActionData = {
                action: actionButton.dataset.action,
                order_id: actionButton.dataset.orderId,
                value: actionButton.dataset.value || null,
                whatsappPhone: actionButton.dataset.whatsappPhone || null,
                whatsappOrderId: actionButton.dataset.whatsappOrderId || null
            };
            confirmationModalBody.textContent = actionButton.dataset.confirmMessage || 'هل أنت متأكد من رغبتك في المتابعة؟';
            
            // تغيير نص زر التأكيد بناءً على الإجراء
            if (currentActionData.whatsappPhone) {
                confirmActionBtn.textContent = 'متأكد وإرسال واتساب';
            } else {
                confirmActionBtn.textContent = 'تأكيد';
            }

            confirmationModal.show();
        }
    });

    confirmActionBtn.addEventListener('click', function () {
        fetch('ajax_order_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(currentActionData)
        })
        .then(response => response.json())
        .then(data => {
            const feedbackDiv = document.getElementById('status-update-feedback');
            const alertClass = data.success ? 'alert-success' : 'alert-danger';
            if (feedbackDiv) {
                feedbackDiv.innerHTML = `<div class="alert ${alertClass}">${data.message}</div>`;
            }
            if (data.success) {
                // إذا نجح الإجراء وكان يتطلب إرسال واتساب
                if (currentActionData.whatsappPhone && currentActionData.whatsappOrderId) {
                    const phone = currentActionData.whatsappPhone.replace(/[^0-9]/g, '');
                    const message = encodeURIComponent(`عميلنا العزيز، طلبكم رقم #${currentActionData.whatsappOrderId} جاهز للاستلام. شكراً لتعاملكم مع إنجاز الإعلامية.`);
                    const whatsappUrl = `https://wa.me/966${phone.substr(-9)}?text=${message}`; // استخدام آخر 9 أرقام مع مفتاح الدولة
                    window.open(whatsappUrl, '_blank');
                }
                setTimeout(() => window.location.reload(), 1500);
            }
        })
        .catch(console.error)
        .finally(() => {
            confirmationModal.hide();
        });
    });
});

// --- Web Push Notifications Setup ---
document.addEventListener('DOMContentLoaded', function () {
    // **هام جداً:** استبدل هذا المفتاح بالمفتاح العام (Public Key) الذي قمت بإنشائه
    const VAPID_PUBLIC_KEY = 'BOO6hReDhGKEWlAlGekjIsorm_s8NMuhCvlFuIxc-LpSZ7RBlf4bjD-2mt0kZXSw07dDoOoUb_eneVr2JllwOc4';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function subscribeUser() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            // إرسال بيانات الاشتراك إلى الخادم
            await fetch('save-subscription.php', {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            console.log('User is subscribed.');
        } catch (error) {
            console.error('Failed to subscribe the user: ', error);
        }
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('service-worker.js').then(function(registration) {
            console.log('Service Worker registered with scope:', registration.scope);
            // طلب الإذن بمجرد تسجيل الدخول
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    console.log('Notification permission granted.');
                    subscribeUser();
                }
            });
        }).catch(function(error) {
            console.error('Service Worker registration failed:', error);
        });
    }
});
</script>
</body>
</html>
