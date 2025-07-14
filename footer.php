    </main>
  </div>
</div>
<footer class="text-center py-4 mt-5" style="background:#fff;color:#D44759;">
    &copy; <?= date('Y') ?> إنجاز الإعلامية — جميع الحقوق محفوظة.
</footer>
<!-- Chart.js for creating charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Bootstrap JS (ضروري لبعض مكونات الواجهة مثل التنبيهات القابلة للإغلاق) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

    function updateCountdown() {
        countdownElements.forEach(el => {
            const now = new Date();

            // عداد الوقت المنقضي (منذ إنشاء الطلب)
            if (el.dataset.orderDate) {
                const orderDate = new Date(el.dataset.orderDate);
                const diff = now - orderDate;

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                let output = 'منذ ';
                if (days > 0) {
                    output += `<span class="fs-5">${days}</span> يوم و <span class="fs-5">${hours}</span> ساعة`;
                } else if (hours > 0) {
                    output += `<span class="fs-5">${hours}</span> ساعة و <span class="fs-5">${minutes}</span> دقيقة و <span class="fs-5">${seconds}</span> ثانية`;
                } else {
                    output += `<span class="fs-5">${minutes}</span> دقيقة و <span class="fs-5">${seconds}</span> ثانية`;
                }
                el.innerHTML = output;
            } 
            // عداد الوقت المتبقي (حتى تاريخ التسليم) - يمكن استخدامه في صفحات أخرى
            else if (el.dataset.dueDate) {
                const dueDate = new Date(el.dataset.dueDate + 'T23:59:59');
                const diff = dueDate - now;

                if (diff <= 0) {
                    el.innerHTML = '<span class="text-danger fw-bold">انتهى الوقت!</span>';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                let output = 'متبقي: ';
                if (days > 0) output += `<span class="fs-5">${days}</span> يوم و <span class="fs-5">${hours}</span> ساعة`;
                else if (hours > 0) output += `<span class="fs-5">${hours}</span> ساعة و <span class="fs-5">${minutes}</span> دقيقة`;
                else output += `<span class="fs-5">${minutes}</span> دقيقة`;
                el.innerHTML = output;
            }
        });
    }

    // تشغيل العداد إذا وجدت عناصر للعد التنازلي
    if (countdownElements.length > 0) {
        updateCountdown(); // التشغيل أول مرة فوراً
        setInterval(updateCountdown, 1000); // تحديث كل ثانية
    }
});
</script>
</body>
</html>
