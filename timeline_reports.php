<?php
$page_title = 'الجدول الزمني للمراحل';
include 'db_connection.php';
include 'header.php';

// التحقق من الصلاحيات
if (!has_permission('dashboard_reports_view', $conn) && !has_permission('order_view_own', $conn)) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? 'guest';

// بناء الاستعلام حسب الصلاحيات
$where_clause = "";
$params = [];
$types = "";

if (!has_permission('dashboard_reports_view', $conn)) {
    // إذا لم يكن مديراً، اعرض فقط الطلبات التي يخصه
    switch ($user_role) {
        case 'مصمم':
            $where_clause = "WHERE o.designer_id = ?";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'معمل':
            $where_clause = "WHERE o.workshop_id = ?";
            $params[] = $user_id;
            $types .= "i";
            break;
        case 'محاسب':
            $where_clause = "WHERE o.status != 'ملغي'";
            break;
        default:
            $where_clause = "WHERE 1=0"; // لا يعرض شيء للأدوار الأخرى
            break;
    }
}

// استعلام جلب البيانات
$sql = "SELECT o.order_id, o.order_date, o.status, o.design_completed_at, o.execution_completed_at, 
               o.delivered_at, o.design_rating, o.execution_rating, c.company_name as client_name, 
               e.name as designer_name, w.name as workshop_name,
               COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        LEFT JOIN employees e ON o.designer_id = e.employee_id
        LEFT JOIN employees w ON o.workshop_id = w.employee_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        {$where_clause}
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// دالة لحساب مدة المرحلة
function calculate_stage_duration($start_date, $end_date) {
    if (empty($start_date) || empty($end_date)) {
        return null;
    }
    
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $end->getTimestamp() - $start->getTimestamp();
    
    if ($diff <= 0) return null;
    
    $days = floor($diff / (24 * 60 * 60));
    $hours = floor(($diff % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($diff % (60 * 60)) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = "{$days} يوم";
    if ($hours > 0) $parts[] = "{$hours} ساعة";
    if ($minutes > 0) $parts[] = "{$minutes} دقيقة";
    
    return empty($parts) ? "أقل من دقيقة" : implode(' و ', $parts);
}

// دالة لحساب المدة الحالية للمرحلة النشطة
function calculate_current_stage_duration($start_date) {
    if (empty($start_date)) return null;
    
    $start = new DateTime($start_date);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $start->getTimestamp();
    
    if ($diff <= 0) return null;
    
    $days = floor($diff / (24 * 60 * 60));
    $hours = floor(($diff % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($diff % (60 * 60)) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = "{$days} يوم";
    if ($hours > 0) $parts[] = "{$hours} ساعة";
    if ($minutes > 0) $parts[] = "{$minutes} دقيقة";
    
    return empty($parts) ? "أقل من دقيقة" : implode(' و ', $parts);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        الجدول الزمني للمراحل
                        <?php if (!has_permission('dashboard_reports_view', $conn)): ?>
                            <small class="ms-2">(المهام المخصصة لك فقط)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>العميل</th>
                                    <th>المنتجات</th>
                                    <th>المصمم</th>
                                    <th>المعمل</th>
                                    <th>الحالة الحالية</th>
                                    <th>مرحلة التصميم</th>
                                    <th>مرحلة التنفيذ</th>
                                    <th>إجمالي الوقت</th>
                                    <?php if (has_permission('dashboard_reports_view', $conn)): ?>
                                        <th>التقييم</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <?php
                                        // حساب مدد المراحل
                                        $design_duration = calculate_stage_duration($row['order_date'], $row['design_completed_at']);
                                        $execution_duration = calculate_stage_duration($row['design_completed_at'], $row['execution_completed_at']);
                                        
                                        // حساب المدة الحالية للمرحلة النشطة
                                        $current_stage_duration = null;
                                        if ($row['status'] === 'قيد التصميم') {
                                            $current_stage_duration = calculate_current_stage_duration($row['order_date']);
                                        } elseif ($row['status'] === 'قيد التنفيذ' && !empty($row['design_completed_at'])) {
                                            $current_stage_duration = calculate_current_stage_duration($row['design_completed_at']);
                                        }
                                        
                                        // حساب إجمالي الوقت
                                        $total_duration = null;
                                        if ($row['status'] === 'مكتمل' && !empty($row['delivered_at'])) {
                                            $total_duration = calculate_stage_duration($row['order_date'], $row['delivered_at']);
                                        } else {
                                            $total_duration = calculate_current_stage_duration($row['order_date']);
                                        }
                                        ?>
                                        <tr>
                                            <td><strong>#<?= $row['order_id'] ?></strong></td>
                                            <td><?= htmlspecialchars($row['client_name']) ?></td>
                                            <td style="max-width: 200px;">
                                                <small><?= htmlspecialchars($row['products_summary']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['designer_name'] ?? 'غير محدد') ?></td>
                                            <td><?= htmlspecialchars($row['workshop_name'] ?? 'غير محدد') ?></td>
                                            <td>
                                                <span class="badge <?= get_status_class($row['status']) ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($design_duration): ?>
                                                    <span class="text-success">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        <?= $design_duration ?>
                                                    </span>
                                                <?php elseif ($row['status'] === 'قيد التصميم'): ?>
                                                    <span class="text-warning">
                                                        <i class="bi bi-clock me-1"></i>
                                                        جاري: <?= $current_stage_duration ?? 'غير محدد' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">لم تبدأ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($execution_duration): ?>
                                                    <span class="text-success">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        <?= $execution_duration ?>
                                                    </span>
                                                <?php elseif ($row['status'] === 'قيد التنفيذ'): ?>
                                                    <span class="text-primary">
                                                        <i class="bi bi-clock me-1"></i>
                                                        جاري: <?= $current_stage_duration ?? 'غير محدد' ?>
                                                    </span>
                                                <?php elseif (in_array($row['status'], ['جاهز للتسليم', 'مكتمل'])): ?>
                                                    <span class="text-success">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        مكتمل
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">لم تبدأ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-dark">
                                                    <?= $total_duration ?? 'غير محدد' ?>
                                                </strong>
                                            </td>
                                            <?php if (has_permission('dashboard_reports_view', $conn)): ?>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        <!-- تقييم التصميم -->
                                                        <?php if ($design_duration || $row['status'] === 'قيد التصميم'): ?>
                                                            <div class="rating-section">
                                                                <small class="text-muted">التصميم:</small>
                                                                <div class="rating-stars" data-order-id="<?= $row['order_id'] ?>" data-stage="design">
                                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                                        <span class="star <?= $i <= ($row['design_rating'] ?? 0) ? 'active' : '' ?>" 
                                                                              data-rating="<?= $i ?>" 
                                                                              title="<?= $i ?>/10">★</span>
                                                                    <?php endfor; ?>
                                                                </div>
                                                                <?php if ($row['design_rating']): ?>
                                                                    <small class="text-success"><?= $row['design_rating'] ?>/10</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- تقييم التنفيذ -->
                                                        <?php if ($execution_duration || $row['status'] === 'قيد التنفيذ' || in_array($row['status'], ['جاهز للتسليم', 'مكتمل'])): ?>
                                                            <div class="rating-section">
                                                                <small class="text-muted">التنفيذ:</small>
                                                                <div class="rating-stars" data-order-id="<?= $row['order_id'] ?>" data-stage="execution">
                                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                                        <span class="star <?= $i <= ($row['execution_rating'] ?? 0) ? 'active' : '' ?>" 
                                                                              data-rating="<?= $i ?>" 
                                                                              title="<?= $i ?>/10">★</span>
                                                                    <?php endfor; ?>
                                                                </div>
                                                                <?php if ($row['execution_rating']): ?>
                                                                    <small class="text-success"><?= $row['execution_rating'] ?>/10</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            لا توجد بيانات لعرضها
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    font-size: 0.9rem;
    font-weight: 600;
}

.table td {
    font-size: 0.85rem;
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}

.card-header {
    background: linear-gradient(135deg, #D44759, #F37D47) !important;
}

.table-dark {
    background: linear-gradient(135deg, #343a40, #495057) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(212, 71, 89, 0.1) !important;
}

/* تنسيق التقييم بالنجوم */
.rating-section {
    margin-bottom: 8px;
}

.rating-stars {
    display: flex;
    gap: 2px;
    margin: 2px 0;
}

.star {
    font-size: 14px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
    user-select: none;
}

.star:hover,
.star.hover {
    color: #ffc107;
}

.star.active {
    color: #ffc107;
}

.rating-stars:hover .star {
    color: #ddd;
}

.rating-stars:hover .star:hover,
.rating-stars:hover .star.hover {
    color: #ffc107;
}

.rating-stars:hover .star:hover ~ .star {
    color: #ddd;
}

.rating-section small {
    font-size: 0.75rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // معالجة التقييم بالنجوم
    document.querySelectorAll('.rating-stars').forEach(function(ratingContainer) {
        const stars = ratingContainer.querySelectorAll('.star');
        const orderId = ratingContainer.dataset.orderId;
        const stage = ratingContainer.dataset.stage;
        
        stars.forEach(function(star, index) {
            // عند التمرير فوق النجمة
            star.addEventListener('mouseenter', function() {
                highlightStars(stars, index + 1);
            });
            
            // عند النقر على النجمة
            star.addEventListener('click', function() {
                const rating = parseInt(star.dataset.rating);
                submitRating(orderId, stage, rating, ratingContainer);
            });
        });
        
        // إعادة تعيين التمييز عند مغادرة منطقة النجوم
        ratingContainer.addEventListener('mouseleave', function() {
            resetStars(stars);
        });
    });
    
    function highlightStars(stars, count) {
        stars.forEach(function(star, index) {
            if (index < count) {
                star.classList.add('hover');
            } else {
                star.classList.remove('hover');
            }
        });
    }
    
    function resetStars(stars) {
        stars.forEach(function(star) {
            star.classList.remove('hover');
        });
    }
    
    function submitRating(orderId, stage, rating, container) {
        // إظهار مؤشر التحميل
        Swal.fire({
            title: 'جاري حفظ التقييم...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch('ajax_update_rating.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                stage: stage,
                rating: rating
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث النجوم لتعكس التقييم الجديد
                const stars = container.querySelectorAll('.star');
                stars.forEach(function(star, index) {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
                
                // تحديث النص إذا وجد
                const ratingText = container.parentElement.querySelector('small.text-success');
                if (ratingText) {
                    ratingText.textContent = rating + '/10';
                } else {
                    // إنشاء نص التقييم إذا لم يكن موجوداً
                    const newRatingText = document.createElement('small');
                    newRatingText.className = 'text-success';
                    newRatingText.textContent = rating + '/10';
                    container.parentElement.appendChild(newRatingText);
                }
                
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire('خطأ!', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('خطأ فني!', 'حدث خطأ أثناء حفظ التقييم.', 'error');
        });
    }
});
</script>

<?php include 'footer.php'; ?>
