<?php
// This file is included from Timeline.php, so it has access to all the variables.
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        الجدول الزمني للمراحل
                    </h5>
                    <?php if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn)): ?>
                        <form method="get" class="d-flex align-items-center">
                            <select name="employee" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                                <option value="">كل الموظفين</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($employee['name']) . ' (' . htmlspecialchars($employee['role']) . ')' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button type="submit" class="btn btn-sm btn-light">تطبيق</button></noscript>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center">
                            <thead class="table-dark align-middle">
                                <tr>
                                    <th rowspan="2">رقم الطلب</th>
                                    <th colspan="5" class="bg-info bg-opacity-25">مرحلة التصميم</th>
                                    <th colspan="5" class="bg-primary bg-opacity-25">مرحلة التنفيذ</th>
                                    <th rowspan="2">إجمالي الوقت</th>
                                </tr>
                                <tr>
                                    <th class="bg-info bg-opacity-10">المصمم</th>
                                    <th class="bg-info bg-opacity-10">البداية</th>
                                    <th class="bg-info bg-opacity-10">النهاية</th>
                                    <th class="bg-info bg-opacity-10">الوقت</th>
                                    <th class="bg-info bg-opacity-10">التقييم</th>
                                    <th class="bg-primary bg-opacity-10">المعمل</th>
                                    <th class="bg-primary bg-opacity-10">البداية</th>
                                    <th class="bg-primary bg-opacity-10">النهاية</th>
                                    <th class="bg-primary bg-opacity-10">الوقت</th>
                                    <th class="bg-primary bg-opacity-10">التقييم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <?php
                                        $design_duration = calculate_stage_duration($row['order_date'], $row['design_completed_at']);
                                        $execution_duration = calculate_stage_duration($row['design_completed_at'], $row['execution_completed_at']);
                                        
                                        $total_duration = null;
                                        if ($row['status'] === 'مكتمل' && !empty($row['delivered_at'])) {
                                            $total_duration = calculate_stage_duration($row['order_date'], $row['delivered_at']);
                                        } else {
                                            $total_duration = calculate_current_stage_duration($row['order_date']);
                                        }
                                        ?>
                                        <tr>
                                            <td><strong>#<?= $row['order_id'] ?></strong></td>
                                            
                                            <td><?= htmlspecialchars($row['designer_name'] ?? 'N/A') ?></td>
                                            <td><small><?= !empty($row['order_date']) ? date('Y-m-d H:i', strtotime($row['order_date'])) : 'N/A' ?></small></td>
                                            <td><small><?= !empty($row['design_completed_at']) ? date('Y-m-d H:i', strtotime($row['design_completed_at'])) : '-' ?></small></td>
                                            <td><span class="badge bg-light text-dark"><?= $design_duration ?? '-' ?></span></td>
                                            <td>
                                                <div class="rating-stars" data-order-id="<?= $row['order_id'] ?>" data-stage="design">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?= $i <= ($row['design_rating'] / 2) ? 'active' : '' ?>" data-rating="<?= $i ?>" title="<?= $i ?>/5">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>

                                            <td><?= htmlspecialchars($row['workshop_name'] ?? 'N/A') ?></td>
                                            <td><small><?= !empty($row['design_completed_at']) ? date('Y-m-d H:i', strtotime($row['design_completed_at'])) : '-' ?></small></td>
                                            <td><small><?= !empty($row['execution_completed_at']) ? date('Y-m-d H:i', strtotime($row['execution_completed_at'])) : '-' ?></small></td>
                                            <td><span class="badge bg-light text-dark"><?= $execution_duration ?? '-' ?></span></td>
                                            <td>
                                                <div class="rating-stars" data-order-id="<?= $row['order_id'] ?>" data-stage="execution">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?= $i <= ($row['execution_rating'] / 2) ? 'active' : '' ?>" data-rating="<?= $i ?>" title="<?= $i ?>/5">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>

                                            <td><strong class="badge bg-dark"><?= $total_duration ?? '-' ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
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
.table-bordered th, .table-bordered td {
    border-color: #dee2e6;
}
.table th {
    font-size: 0.85rem;
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

.rating-stars {
    display: flex;
    gap: 2px;
    margin: 2px 0;
}

.star {
    font-size: 18px;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rating-stars').forEach(function(ratingContainer) {
        const stars = ratingContainer.querySelectorAll('.star');
        const orderId = ratingContainer.dataset.orderId;
        const stage = ratingContainer.dataset.stage;
        
        stars.forEach(function(star, index) {
            star.addEventListener('mouseenter', function() {
                highlightStars(stars, index + 1);
            });
            
            star.addEventListener('click', function() {
                const rating = parseInt(star.dataset.rating) * 2;
                submitRating(orderId, stage, rating, ratingContainer);
            });
        });
        
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
        Swal.fire({
            title: 'جاري حفظ التقييم...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch('/api/ratings', {
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
                const stars = container.querySelectorAll('.star');
                stars.forEach(function(star, index) {
                    if (index < (rating / 2)) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
                
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
