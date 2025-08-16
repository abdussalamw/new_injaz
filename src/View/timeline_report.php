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
                        <form method="get" class="row g-2 align-items-center mb-0" style="width:100%;">
                            <div class="col-md-3">
                                <label for="date_from" class="form-label text-white mb-1">من تاريخ</label>
                                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label text-white mb-1">إلى تاريخ</label>
                                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="employee" class="form-label text-white mb-1">الموظف</label>
                                <select name="employee" id="employee" class="form-select form-select-sm">
                                    <option value="">كل الموظفين</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['employee_id'] ?>" <?= $filter_employee == $employee['employee_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($employee['name']) . ' (' . htmlspecialchars($employee['role']) . ')' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 align-self-end">
                                <button type="submit" class="btn btn-light btn-sm w-100 mt-2">تطبيق الفلتر</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover text-center table-sm">
                            <thead class="align-middle">
                                <tr>
                                    <th rowspan="2" style="width: 8%;">رقم الطلب</th>
                                    <th colspan="5" class="bg-info bg-opacity-25">مرحلة التصميم</th>
                                    <th colspan="5" class="bg-primary bg-opacity-25">مرحلة التنفيذ</th>
                                    <th rowspan="2" style="width: 10%;">إجمالي الوقت</th>
                                </tr>
                                <tr>
                                    <th class="bg-info bg-opacity-10" style="width: 10%;">المصمم</th>
                                    <th class="bg-info bg-opacity-10" style="width: 8%;">البداية</th>
                                    <th class="bg-info bg-opacity-10" style="width: 8%;">النهاية</th>
                                    <th class="bg-info bg-opacity-10" style="width: 8%;">الوقت</th>
                                    <th class="bg-info bg-opacity-10" style="width: 8%;">التقييم</th>
                                    <th class="bg-primary bg-opacity-10" style="width: 10%;">المعمل</th>
                                    <th class="bg-primary bg-opacity-10" style="width: 8%;">البداية</th>
                                    <th class="bg-primary bg-opacity-10" style="width: 8%;">النهاية</th>
                                    <th class="bg-primary bg-opacity-10" style="width: 8%;">الوقت</th>
                                    <th class="bg-primary bg-opacity-10" style="width: 8%;">التقييم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <?php
                                        $design_duration_seconds = \App\Core\Helpers::calculate_stage_duration($row['order_date'], $row['design_completed_at']);
                                        $execution_duration_seconds = \App\Core\Helpers::calculate_stage_duration($row['design_completed_at'], $row['execution_completed_at']);
                                        
                                        $design_duration = $design_duration_seconds ? \App\Core\Helpers::format_duration($design_duration_seconds) : null;
                                        $execution_duration = $execution_duration_seconds ? \App\Core\Helpers::format_duration($execution_duration_seconds) : null;
                                        
                                        $total_duration = null;
                                        if ($row['status'] === 'مكتمل' && !empty($row['delivered_at'])) {
                                            $total_duration_seconds = \App\Core\Helpers::calculate_stage_duration($row['order_date'], $row['delivered_at']);
                                            $total_duration = $total_duration_seconds ? \App\Core\Helpers::format_duration($total_duration_seconds) : null;
                                        } else {
                                            $total_duration_seconds = \App\Core\Helpers::calculate_current_stage_duration($row['order_date']);
                                            $total_duration = $total_duration_seconds ? \App\Core\Helpers::format_duration($total_duration_seconds) : null;
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= $_ENV['BASE_PATH'] ?>/orders/edit?id=<?= $row['order_id'] ?>" style="text-decoration:none;font-weight:bold;">
                                                    #<?= $row['order_id'] ?>
                                                </a>
                                            </td>
                                            
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

                                            <td>
                                                <?php 
                                                // عرض اسم المعمل فقط إذا بدأت مرحلة التنفيذ فعلياً
                                                if (!empty($row['design_completed_at']) && $row['status'] !== 'قيد التصميم') {
                                                    echo htmlspecialchars($row['workshop_name'] ?? 'غير معين');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php 
                                                    // عرض تاريخ بداية التنفيذ فقط إذا بدأت مرحلة التنفيذ فعلياً
                                                    if (!empty($row['design_completed_at']) && $row['status'] !== 'قيد التصميم') {
                                                        echo date('Y-m-d H:i', strtotime($row['design_completed_at']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php 
                                                    // عرض تاريخ نهاية التنفيذ فقط إذا انتهت مرحلة التنفيذ فعلياً
                                                    if (!empty($row['execution_completed_at'])) {
                                                        echo date('Y-m-d H:i', strtotime($row['execution_completed_at']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= (!empty($row['design_completed_at']) && $row['status'] !== 'قيد التصميم') ? ($execution_duration ?? '-') : '-' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['design_completed_at']) && $row['status'] !== 'قيد التصميم'): ?>
                                                    <div class="rating-stars" data-order-id="<?= $row['order_id'] ?>" data-stage="execution">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?= $i <= ($row['execution_rating'] / 2) ? 'active' : '' ?>" data-rating="<?= $i ?>" title="<?= $i ?>/5">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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
    const BASE_URL = '<?= $base_path ?>'; // Defined in public/index.php
    console.log('BASE_URL:', BASE_URL);
    console.log('Full API URL:', BASE_URL + '/api/ratings');
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
        console.log('submitRating called with:', {orderId, stage, rating});
        console.log('Sending to URL:', BASE_URL + '/api/ratings');

        Swal.fire({
            title: 'جاري حفظ التقييم...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch(BASE_URL + '/api/ratings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                order_id: orderId,
                stage: stage,
                rating: rating
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
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
            console.error('Fetch Error:', error);
            console.error('Error details:', error.message);
            Swal.fire('خطأ فني!', 'حدث خطأ أثناء حفظ التقييم: ' + error.message, 'error');
        });
    }
});
</script>
