    </main>
  </div>
</div>
<footer class="text-center py-4 mt-5" style="background:#fff;color:#D44759;">
    &copy; <?= date('Y') ?> — جميع الحقوق محفوظة.
</footer>
<!-- Chart.js for creating charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- SweetAlert2 for beautiful pop-ups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap JS (ضروري لبعض مكونات الواجهة مثل النوافذ المنبثقة والقوائم المنسدلة) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.4/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- القائمة الجانبية الديناميكية ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle && sidebar && mainContent) {
        // استرجاع حالة القائمة من localStorage
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarToggle.querySelector('i').classList.replace('bi-chevron-right', 'bi-chevron-left');
        }
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.replace('bi-chevron-right', 'bi-chevron-left');
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                icon.classList.replace('bi-chevron-left', 'bi-chevron-right');
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        });
    }

    // --- المؤقتات ---
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
                diff = dueDate - now;
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

            // تنسيق الوقت بكتابة كاملة وخط جميل
            let parts = [];
            
            // إضافة الأيام إذا كانت أكبر من صفر
            if (days > 0) {
                const dayText = days === 1 ? 'يوم واحد' : days === 2 ? 'يومان' : `${days} أيام`;
                parts.push(dayText);
            }
            
            // إضافة الساعات
            if (hours > 0) {
                const hourText = hours === 1 ? 'ساعة واحدة' : hours === 2 ? 'ساعتان' : `${hours} ساعات`;
                parts.push(hourText);
            }
            
            // إضافة الدقائق
            if (minutes > 0) {
                const minuteText = minutes === 1 ? 'دقيقة واحدة' : minutes === 2 ? 'دقيقتان' : `${minutes} دقيقة`;
                parts.push(minuteText);
            }
            
            // إضافة الثواني (بدون كتابة، فقط الرقم)
            const secondsDisplay = String(seconds).padStart(2, '0');

            // تجميع النص النهائي
            let timeText = '';
            if (parts.length > 0) {
                timeText = parts.join(' و ') + ` : ${secondsDisplay}`;
            } else {
                timeText = secondsDisplay; // إذا كان أقل من دقيقة، اعرض الثواني فقط
            }

            el.innerHTML = `<div class="text-center" style="font-family: 'Tajawal', Arial, sans-serif; font-weight: 600; color: #000; font-size: 1.1rem; line-height: 1.4;">${prefix}${timeText}</div>`;
        });
    }

    // تشغيل العداد إذا وجدت عناصر للعد التنازلي
    if (countdownElements.length > 0) {
        updateCountdown(); // التشغيل أول مرة فوراً
        setInterval(updateCountdown, 1000); // تحديث كل ثانية
    }
});

// --- Global SweetAlert2 Action Button Handler (Centralized) ---
document.addEventListener('DOMContentLoaded', function () {
    // استخدام body للاستماع للأحداث لضمان عمله مع العناصر التي تضاف ديناميكياً
    document.body.addEventListener('click', function (e) {
        const actionBtn = e.target.closest('.action-btn');
        if (!actionBtn) return;

        e.preventDefault();

        const orderId = actionBtn.dataset.orderId;
        const action = actionBtn.dataset.action;
        const value = actionBtn.dataset.value || null;
        const confirmMessage = actionBtn.dataset.confirmMessage;
        const whatsappPhone = actionBtn.dataset.whatsappPhone;
        const whatsappOrderId = actionBtn.dataset.whatsappOrderId;

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: confirmMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم, نفّذ الإجراء!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'الرجاء الانتظار...',
                    text: 'جاري تنفيذ الإجراء.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch('ajax_order_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, action: action, value: value })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (whatsappPhone && whatsappOrderId) {
                            const whatsappMessage = `العميل العزيز، تم تحديث حالة طلبكم رقم ${whatsappOrderId}. شكراً لتعاملكم معنا.`;
                            const encodedMessage = encodeURIComponent(whatsappMessage);
                            const internationalPhone = '966' + whatsappPhone.substring(1);
                            const whatsappUrl = `https://wa.me/${internationalPhone}?text=${encodedMessage}`;
                            
                            Swal.fire({
                                title: 'تم بنجاح!',
                                text: data.message + ' سيتم الآن فتح واتساب.',
                                icon: 'success',
                                timer: 2500,
                                timerProgressBar: true
                            }).then(() => {
                                window.open(whatsappUrl, '_blank');
                                location.reload();
                            });
                        } else {
                             const feedbackDiv = document.getElementById('status-update-feedback');
                             if (feedbackDiv) {
                                 feedbackDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                             }
                            Swal.close();
                            setTimeout(() => location.reload(), 1500);
                        }
                    } else {
                        Swal.fire('خطأ!', data.message, 'error');
                    }
                }).catch(error => {
                    Swal.fire('خطأ فني!', 'حدث خطأ غير متوقع.', 'error');
                });
            }
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
        } catch (error) {
            // Failed to subscribe the user
        }
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('service-worker.js').then(function(registration) {
            // طلب الإذن بمجرد تسجيل الدخول
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    subscribeUser();
                }
            });
        }).catch(function(error) {
            // Service Worker registration failed
        });
    }
});
</script>
</body>
</html>
