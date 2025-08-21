<?php
// نسخة مخصصة لصفحة tasks2 بدون عرض حالة الدفع أو شريط الدفع
// منسوخة من card.php مع إزالة مقاطع الدفع فقط

// نفس التهيئة كما في الأصل
$user_role = \App\Core\RoleHelper::getCurrentUserRole();
$user_id = \App\Core\RoleHelper::getCurrentUserId();

if (!function_exists('has_card_permission')) {
    function has_card_permission($permission, $conn) { return \App\Core\Permissions::has_permission($permission, $conn); }
}
if (!function_exists('get_priority_class')) {
    function get_priority_class($priority) { return \App\Core\Helpers::get_priority_class($priority); }
}
if (!function_exists('get_payment_status_display')) {
    function get_payment_status_display($payment_status, $total_amount, $deposit_amount) { return \App\Core\Helpers::get_payment_status_display($payment_status, $total_amount, $deposit_amount); }
}
if (!function_exists('generate_timeline_bar')) {
    function generate_timeline_bar($order) { return \App\Core\Helpers::generate_timeline_bar($order); }
}
if (!function_exists('format_whatsapp_link')) {
    function format_whatsapp_link($phone, $message = '') { return \App\Core\Helpers::format_whatsapp_link($phone, $message); }
}
if (!function_exists('get_current_responsible')) {
    function get_current_responsible($task_details, $conn) {
        $status = trim($task_details['status'] ?? '');
        switch ($status) {
            case 'قيد التصميم': return $task_details['designer_name'] ?? 'غير محدد';
            case 'قيد التنفيذ':
                if (!empty($task_details['workshop_id'])) {
                    $workshop_query = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
                    $workshop_query->bind_param("i", $task_details['workshop_id']);
                    $workshop_query->execute();
                    $workshop_result = $workshop_query->get_result();
                    if ($row = $workshop_result->fetch_assoc()) return $row['name'];
                }
                $fallback = $conn->query("SELECT name FROM employees WHERE role = 'معمل' LIMIT 1");
                if ($fallback && $row = $fallback->fetch_assoc()) return $row['name'];
                return 'المعمل';
            case 'جاهز للتسليم':
                if (!empty($task_details['workshop_id'])) {
                    $workshop_query = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
                    $workshop_query->bind_param("i", $task_details['workshop_id']);
                    $workshop_query->execute();
                    $workshop_result = $workshop_query->get_result();
                    if ($row = $workshop_result->fetch_assoc()) return $row['name'];
                }
                return 'المعمل/المدير';
            case 'مكتمل': return 'مكتمل';
            case 'ملغي': return 'ملغي';
            default: return $task_details['designer_name'] ?? 'غير محدد';
        }
    }
}
// --- عزل اختبار أيقونة زر استلام العميل هنا فقط (لا تغيير على النظام الأساسي) ---
if (isset($actions['confirm_delivery'])) {
  $actions['confirm_delivery']['icon'] = 'bi-check-circle'; // الأيقونة الجديدة التجريبية
}
// مخرجات تشخيصية اختيارية عند إضافة ?debug_icons=1 للعنوان
if (!empty($_GET['debug_icons'])) {
  echo '<!-- DEBUG icons: '.htmlspecialchars(json_encode(array_map(fn($a)=>$a['icon']??'', $actions), JSON_UNESCAPED_UNICODE)).' -->';
}
?>
<div class="card h-100 shadow-sm <?= get_priority_class($task_details['priority']) ?>" style="border-width:4px;border-style:solid;border-top:0;border-right:0;border-bottom:0;">
  <div class="card-body d-flex flex-column position-relative">
    <div class="position-absolute top-0 end-0 mt-2 me-2 text-end">
      <small class="badge bg-secondary d-block mb-1"><?= htmlspecialchars(get_current_responsible($task_details, $conn)) ?></small>
      <?php if (has_card_permission('task_card_view_designer', $conn) && !empty($task_details['designer_name'])): ?>
        <small class="badge bg-info text-dark d-block"><?= htmlspecialchars($task_details['designer_name']) ?></small>
      <?php endif; ?>
    </div>

    <?php if (has_card_permission('task_card_view_summary', $conn)): ?>
      <div class="mb-2">
        <?php foreach (explode(', ', $task_details['products_summary']) as $product): ?>
          <div class="mb-1 fs-5 fw-bold"><?= htmlspecialchars(trim($product)) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php if (has_card_permission('task_card_view_client', $conn)): ?>
      <h6 class="card-subtitle mb-2 text-muted">للعميل: <?= htmlspecialchars($task_details['client_name']) ?> (<?= htmlspecialchars($task_details['order_id']) ?>)</h6>
    <?php endif; ?>

  <?php if (\App\Core\Permissions::has_permission('dashboard_reports_view', $conn) || \App\Core\RoleHelper::isDesigner() || \App\Core\RoleHelper::isWorkshop()): ?>
      <?php
      if (!function_exists('render_timeline_b_compact')) {
      function render_timeline_b_compact(array $t): string {
              // توابع مساعدة محلية
              $fmtTime = function($dtStr) {
                  if (empty($dtStr)) return '...';
                  try { $d=new DateTime($dtStr); return $d->format('d/m H:i'); } catch(Exception $e){ return '...'; }
              };
        // تحديد الدور الحالي لعرض مراحل مخصصة
        $role = \App\Core\RoleHelper::getCurrentUserRole();
        $isDesigner = \App\Core\RoleHelper::isDesigner();
        $isWorkshop = \App\Core\RoleHelper::isWorkshop();

              $now = new DateTime();
              $orderStart = !empty($t['order_date']) ? new DateTime($t['order_date']) : $now; // بداية المهمة العامة
              $designStart = !empty($t['design_started_at']) ? new DateTime($t['design_started_at']) : null; // بداية التصميم فقط إن وُجد ختم
              $designEnd = !empty($t['design_completed_at']) ? new DateTime($t['design_completed_at']) : null;
              $execStart = !empty($t['execution_started_at']) ? new DateTime($t['execution_started_at']) : null;
              $execEnd = !empty($t['execution_completed_at']) ? new DateTime($t['execution_completed_at']) : null;
              $status = $t['status'] ?? '';
              // مدد (بالثواني)
              $designDur = null; $execDur = null; $totalDur = null;
              if ($designEnd && $designStart) {
                  $designDur = $designEnd->getTimestamp() - $designStart->getTimestamp();
              } elseif ($status === 'قيد التصميم' && $designStart) {
                  $designDur = $now->getTimestamp() - $designStart->getTimestamp();
              }
              if ($execEnd && $execStart) {
                  $execDur = $execEnd->getTimestamp() - $execStart->getTimestamp();
              } elseif ($status === 'قيد التنفيذ' && $execStart) {
                  $execDur = $now->getTimestamp() - $execStart->getTimestamp();
              }
              if ($execEnd) {
                  $totalDur = $execEnd->getTimestamp() - $orderStart->getTimestamp();
              } else {
                  $totalDur = $now->getTimestamp() - $orderStart->getTimestamp();
              }
              // تنسيقات مدة
          $fmtDur = function($sec){
            if($sec===null) return '0د';
            if($sec < 60) return '1د'; // أقل من دقيقة نعرض دقيقة واحدة
            $d=floor($sec/86400);
            $h=floor(($sec%86400)/3600);
            $m=floor(($sec%3600)/60);
            $parts=[];
            if($d>0) $parts[]=$d.'ي';
            if($h>0) $parts[]=$h.'س';
            if($m>0 && $d==0) $parts[]=$m.'د';
            return implode(' ',array_slice($parts,0,2));
          };
              $designDurTxt = $designDur!==null ? $fmtDur($designDur) : (($status==='قيد التصميم') ? 'لم يبدأ' : '—');
              $execDurTxt   = $execDur!==null ? $fmtDur($execDur) : (($status==='قيد التنفيذ') ? 'لم يبدأ' : '—');
              $totalDurTxt  = $fmtDur($totalDur);
              // نسب التقدم (حساب تقريبي)
              $designPct = ($designDur && $totalDur>0) ? max(5,min(100, ($designDur/$totalDur)*100)) : 0;
              $execPct = ($execDur && $totalDur>0) ? max(5,min(100, ($execDur/$totalDur)*100)) : 0;
              $remainingPct = 0;
              if ($designPct + $execPct > 100) { $execPct = 100 - $designPct; }
              // حالات مظهرية
              $execClass = $execDur ? 'exec-done' : (($status==='قيد التنفيذ')?'exec-active':'exec-wait');
              $designClass = $designEnd ? 'design-done' : (($status==='قيد التصميم')?'design-active':'design-pending');
              $totalClass = $execEnd ? 'total-done' : 'total-active';
        // منطق إظهار المراحل: للمصمم فقط التصميم، للمعمل فقط التنفيذ، للآخرين (مدير أو أدوار أخرى) الكل
        $showDesign = true; $showExec = true; $showTotal = true;
        if ($isDesigner && !$isWorkshop && $role !== 'مدير') { // مصمم فقط
          $showExec = false; $showTotal = false; // إظهار التصميم فقط أثناء كونه جارياً
        } elseif ($isWorkshop && !$isDesigner && $role !== 'مدير') { // معمل فقط
          $showDesign = false; $showTotal = false; // إظهار التنفيذ فقط
        }

        // منطق خاص للمصمم: بعد انتهاء التصميم وإرسال المهمة للتنفيذ لا يظهر المخطط الزمني إطلاقاً
        if ($isDesigner && $designEnd && $status !== 'قيد التصميم') {
          return ''; // إخفاء كامل للمخطط بعد الإرسال
        }

        ob_start(); ?>
              <?php if(!defined('TIMELINE_B_COMPACT_CSS')): define('TIMELINE_B_COMPACT_CSS', true); ?>
              <style>
              .timeline-b-compact{direction:rtl;font-size:11px;background:#f8f9fa;border:1px solid #e3e6e9;border-radius:10px;padding:8px 10px;margin-bottom:10px}
              .timeline-b-compact .header-line{display:flex;flex-wrap:wrap;gap:8px 14px;margin-bottom:6px}
              .timeline-b-compact .time-pair{display:flex;gap:4px;align-items:center;color:#555}
              .timeline-b-compact .time-pair span.label{font-weight:600;color:#222}
              .timeline-b-compact .phase-bars{display:flex;gap:6px}
              .timeline-b-compact .phase{flex:1;display:flex;flex-direction:column;gap:4px;min-width:0;background:#eef6ff;border:1px solid #d3e6f7;padding:6px 6px 8px;border-radius:6px;position:relative}
              .timeline-b-compact .phase.exec{background:#f2ffe8;border-color:#d7f3c0}
              .timeline-b-compact .phase.total{background:#f4ecff;border-color:#e1d6ff}
              .timeline-b-compact .phase .ph-title{display:flex;flex-direction:row;align-items:center;gap:4px;font-size:10px;font-weight:400;color:#222;margin:0;line-height:1.1;white-space:nowrap}
              .timeline-b-compact .phase .ph-title .title-line{font-weight:400}
              .timeline-b-compact .phase .ph-title .dur-line{font-size:10px;font-weight:400;color:#111;font-family:inherit}
              .timeline-b-compact .phase.total .ph-title{color:#5a299f}
              .timeline-b-compact .phase .dur{font-weight:600}
              .timeline-b-compact .phase .period{font-size:10px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
              .timeline-b-compact .phase .period{display:none !important}
              .timeline-b-compact .phase .progress{height:5px;background:#d2e9fb;border-radius:3px;overflow:hidden;margin-top:auto;position:relative}
              .timeline-b-compact .phase.exec .progress{background:#e0f5cc}
              .timeline-b-compact .phase.total .progress{background:#e9defd}
              .timeline-b-compact .phase .progress span{display:block;height:100%;background:#0d6efd}
              .timeline-b-compact .phase.exec .progress span{background:#55b61d}
              .timeline-b-compact .phase.total .progress span{background:#8845e6}
              .timeline-b-compact .phase.design-active{outline:1px dashed #0d6efd}
              .timeline-b-compact .phase.exec-active{outline:1px dashed #55b61d}
              .timeline-b-compact .summary-line{margin-top:6px;font-size:10px;color:#444;display:flex;justify-content:flex-end;gap:12px}
              .timeline-b-compact .header-line .time-pair.stacked{display:flex;flex-direction:column;align-items:flex-start;padding:0 2px 0 2px}
              .timeline-b-compact .header-line .time-pair.stacked .label{font-size:11px;color:#222;margin-bottom:2px;font-weight:700}
              .timeline-b-compact .header-line .time-pair.stacked .date-time{font-size:10px;font-family:monospace;font-weight:600;color:#444;line-height:1.1}
              .timeline-b-compact .header-line .time-pair.inline-mid{display:flex;align-items:center;gap:4px}
              .timeline-b-compact .header-line .time-pair.inline-mid .date-time{font-family:monospace;font-size:11px;color:#555}
              @media(max-width:520px){.timeline-b-compact .phase-bars{flex-direction:column}.timeline-b-compact .phase{flex:none}}
              </style>
              <?php endif; ?>
              <?php if(!($isDesigner && !$designEnd)): // للمصمم أثناء التصميم فقط نعرض الرأس، وإلا نخفي الرأس ?>
              <div class="timeline-b-compact">
                <div class="header-line">
                  <div class="time-pair stacked">
                    <span class="label">بدء التصميم</span>
                    <?php $orderDateObj = $orderStart; ?>
                    <span class="date-time"><?= htmlspecialchars($orderDateObj->format('Y-m-d H:i')) ?></span>
                  </div>
                  <?php if(!$isDesigner): ?>
                  <div class="time-pair stacked">
                    <span class="label">انتهاء التصميم</span>
                    <span class="date-time"><?= $designEnd? htmlspecialchars($designEnd->format('Y-m-d H:i')) : '...' ?></span>
                  </div>
                  <div class="time-pair stacked">
                    <span class="label">انتهاء التنفيذ</span>
                    <span class="date-time"><?= $execEnd? htmlspecialchars($execEnd->format('Y-m-d H:i')) : (($status==='قيد التنفيذ')?'جاري':'...') ?></span>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="phase-bars">
                  <?php if($showDesign): ?>
                  <div class="phase design <?= $designClass ?>">
                    <div class="ph-title"><span class="title-line">تصميم</span><span class="dur-line<?php if(!$designEnd && $status==='قيد التصميم' && $designStart): ?> live-dur<?php endif; ?>"<?php if(!$designEnd && $status==='قيد التصميم' && $designStart): ?> data-start="<?= (int)$designStart->getTimestamp()*1000 ?>"<?php endif; ?>><?= htmlspecialchars($designDurTxt) ?></span></div>
                    <div class="period">
                      <?php if($designEnd): ?><?= htmlspecialchars($orderStart->format('H:i')) ?> → <?= htmlspecialchars($designEnd->format('H:i')) ?><?php elseif($status==='قيد التصميم'): ?>من <?= htmlspecialchars($orderStart->format('H:i')) ?> (جاري)<?php else: ?>—<?php endif; ?>
                    </div>
                    <div class="progress"><span style="width:<?= (int)$designPct ?>%"></span></div>
                  </div>
                  <?php endif; ?>
                  <?php if($showExec && !$isDesigner): ?>
                  <div class="phase exec <?= $execClass ?>">
                    <div class="ph-title"><span class="title-line">تنفيذ</span><span class="dur-line<?php if(!$execEnd && $status==='قيد التنفيذ' && $execStart): ?> live-dur<?php endif; ?>"<?php if(!$execEnd && $status==='قيد التنفيذ' && $execStart): ?> data-start="<?= (int)$execStart->getTimestamp()*1000 ?>"<?php endif; ?>><?= htmlspecialchars($execDurTxt) ?></span></div>
                    <div class="period">
                      <?php if($execEnd && $designEnd): ?><?= htmlspecialchars($designEnd->format('H:i')) ?> → <?= htmlspecialchars($execEnd->format('H:i')) ?><?php elseif($status==='قيد التنفيذ' && $designEnd): ?>من <?= htmlspecialchars($designEnd->format('H:i')) ?> (جاري)<?php else: ?>—<?php endif; ?>
                    </div>
                    <div class="progress"><span style="width:<?= (int)$execPct ?>%"></span></div>
                  </div>
                  <?php endif; ?>
                  <?php if($showTotal && !$isDesigner): ?>
                  <div class="phase total <?= $totalClass ?>">
                    <?php $totalLive = !$execEnd; ?>
                    <div class="ph-title"><span class="title-line">المهمة</span><span class="dur-line<?= $totalLive ? ' live-dur' : '' ?>"<?= $totalLive ? ' data-start="'.((int)$orderStart->getTimestamp()*1000).'" data-format="total"' : '' ?>><?= htmlspecialchars($totalDurTxt) ?></span></div>
                    <div class="period">من <?= htmlspecialchars($orderStart->format('H:i')) ?> إلى <?= $execEnd? htmlspecialchars($execEnd->format('H:i')) : 'الآن' ?></div>
                    <div class="progress"><span style="width:100%"></span></div>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php if(!defined('TIMELINE_B_COMPACT_JS')): define('TIMELINE_B_COMPACT_JS', true); ?>
              <script>
              (function(){
                function fmtPhase(sec){
                  if(sec<0) sec=0;
                  if(sec<60) return (sec<=1?1:sec)+'ث';
                  if(sec<3600){
                    var m=Math.floor(sec/60);
                    return m+'د';
                  }
                  var d=Math.floor(sec/86400);
                  var hTotal=Math.floor(sec/3600);
                  var hDay=Math.floor((sec%86400)/3600);
                  var mRem=Math.floor((sec%3600)/60);
                  if(d>0){
                    return d+'ي '+(hDay>0? hDay+'س':'');
                  }
                  return hTotal+'س'+(mRem>0? ' '+mRem+'د':'');
                }
                function fmtTotal(sec){
                  if(sec<0) sec=0;
                  if(sec<60) return (sec<=1?1:sec)+'ث';
                  var hTotal=Math.floor(sec/3600);
                  var m=Math.floor((sec%3600)/60);
                  var s=sec%60;
                  var hh=(hTotal<10? '0'+hTotal:hTotal);
                  var mm=(m<10? '0'+m:m);
                  var ss=(s<10? '0'+s:s);
                  return hh+':'+mm+':'+ss;
                }
                function tick(){
                  var now=Date.now();
                  var nodes=document.querySelectorAll('.timeline-b-compact .live-dur');
                  if(!nodes.length) return;
                  nodes.forEach(function(el){
                    var start=parseInt(el.getAttribute('data-start'),10); if(!start) return; var sec=Math.floor((now-start)/1000);
                    var isTotal = el.getAttribute('data-format')==='total';
                    var txt = isTotal ? fmtTotal(sec) : fmtPhase(sec);
                    el.textContent='منذ '+txt;
                    var hh=Math.floor(sec/3600), mm=Math.floor((sec%3600)/60), ss=sec%60;
                    el.title = (isTotal? 'إجمالي' : 'المدة')+': '+(hh<10?'0'+hh:hh)+':' + (mm<10?'0'+mm:mm)+':' + (ss<10?'0'+ss:ss)+' ('+sec+'ث)';
                  });
                }
                var intervalId=null;
                function start(){ if(intervalId) return; tick(); intervalId=setInterval(tick,1000); }
                function stop(){ if(intervalId){ clearInterval(intervalId); intervalId=null; } }
                document.addEventListener('visibilitychange', function(){ if(document.hidden) stop(); else start(); });
                start();
              })();
              </script>
              <?php endif; ?>
              <?php return ob_get_clean();
          }
      }
      ?>
      <div class="mb-3">
        <small class="text-muted d-block mb-1" style="font-size:0.8rem;">الجدول الزمني (نسخة B-Compact تجريبية)</small>
        <?= render_timeline_b_compact($task_details) ?>
      </div>
    <?php endif; ?>

    <div class="mt-auto">
  <!-- تمت إزالة العداد السفلي المستقل (countdown) للاعتماد على مخطط الزمن الحي فقط -->

  <div class="task-actions"><!-- wrapper لتمكين أنماط الكبسولات -->
  <div class="row g-2">
        <div class="col-6">
          <?php if (has_card_permission('task_card_edit', $conn)): ?>
            <a href="<?= \App\Core\Helpers::url('/orders/edit?id=' . $task_details['order_id']) ?>" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center" style="height:35px;">
              <i class="bi bi-pencil-square me-1"></i><span class="small">تفاصيل</span>
            </a>
          <?php else: ?>
            <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height:35px;"><i class="bi bi-lock me-1"></i><span class="small">محظور</span></div>
          <?php endif; ?>
        </div>
        <div class="col-6">
          <?php if (has_card_permission('task_card_whatsapp', $conn)): ?>
            <a href="<?= format_whatsapp_link($task_details['client_phone']) ?>" target="_blank" class="btn btn-sm w-100 d-flex align-items-center justify-content-center" style="background-color:#25D366;color:#fff;height:35px;">
              <i class="bi bi-whatsapp me-1"></i><span class="small">واتساب</span>
            </a>
          <?php else: ?>
            <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height:35px;"><i class="bi bi-chat-dots me-1"></i><span class="small">محظور</span></div>
          <?php endif; ?>
        </div>
        <div class="col-6">
          <?php $payment_action = null; foreach ($actions as $k=>$a){ if($k==='confirm_payment'){ $payment_action=$a; break; } } ?>
          <?php if ($payment_action): ?>
            <?php $remaining_amount = max(0, (float)($task_details['total_amount'] ?? 0) - (float)($task_details['deposit_amount'] ?? 0)); ?>
            <button class="btn btn-warning btn-sm w-100 action-btn d-flex align-items-center justify-content-center" data-action="confirm_payment" data-order-id="<?= $task_details['order_id'] ?>" data-confirm-message="هل أنت متأكد من تأكيد استلام الدفع؟" title="المتبقي: <?= (int)$remaining_amount ?>" style="height:35px;">
              <i class="bi bi-cash-coin me-1"></i><span class="small">دفع <?= (int)$remaining_amount ?></span>
            </button>
          <?php else: ?>
            <div class="btn btn-outline-secondary btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height:35px;">
              <i class="bi bi-cash me-1"></i><span class="small">مدفوع</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-6">
          <?php $status_action=null; $confirm_delivery_action=null; foreach ($actions as $ak=>$ad){ if($ak==='change_status'){ $status_action=$ad; } elseif($ak==='confirm_delivery'){ $confirm_delivery_action=$ad; } } ?>
          <?php if ($confirm_delivery_action): ?>
            <button class="btn <?= $confirm_delivery_action['class'] ?> btn-sm w-100 action-btn d-flex align-items-center justify-content-center" data-action="confirm_delivery" data-order-id="<?= $task_details['order_id'] ?>" data-confirm-message="هل أنت متأكد من تأكيد استلام العميل للطلب؟" style="height:35px;">
              <i class="<?= $confirm_delivery_action['icon'] ?> me-1"></i><span class="small">استلام العميل</span>
            </button>
          <?php elseif ($status_action): ?>
            <div class="btn-group w-100">
              <button type="button" class="btn btn-info btn-sm dropdown-toggle w-100 d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" aria-expanded="false" style="height:35px;">
                <i class="bi bi-arrow-repeat me-1"></i><span class="small">حالة</span>
              </button>
              <ul class="dropdown-menu">
                <?php foreach ($status_action['options'] as $next_status => $status_details): ?>
                  <li><a class="dropdown-item action-btn" href="#" data-action="change_status" data-value="<?= htmlspecialchars($next_status) ?>" data-order-id="<?= $task_details['order_id'] ?>" data-confirm-message="<?= htmlspecialchars($status_details['confirm_message']) ?>" <?php if (!empty($status_details['whatsapp_action'])): ?>data-whatsapp-phone="<?= htmlspecialchars($task_details['client_phone']) ?>" data-whatsapp-order-id="<?= $task_details['order_id'] ?>"<?php endif; ?>><?= htmlspecialchars($status_details['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php else: ?>
            <div class="btn btn-success btn-sm w-100 disabled d-flex align-items-center justify-content-center" style="height:35px;"><i class="bi bi-check-circle me-1"></i><span class="small">مكتمل</span></div>
          <?php endif; ?>
        </div>
  </div>
  </div> <!-- /task-actions -->

      <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.action-btn[data-action="change_status"]').forEach(function(btn){
          btn.addEventListener('click', function(e){ e.preventDefault(); const orderId=btn.dataset.orderId; const value=btn.dataset.value; const confirmMsg=btn.dataset.confirmMessage||'هل أنت متأكد؟'; if(!orderId||!value) return; if(confirm(confirmMsg)){ fetch('ajax_order_actions.php',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({order_id:parseInt(orderId),action:'change_status',value:value})}).then(r=>r.json()).then(d=>{ if(d.success){ alert('تم بنجاح: '+d.message); window.location.reload(); } else { alert('خطأ: '+d.message); } }).catch(()=>alert('حدث خطأ فني')); }}); });
        document.querySelectorAll('.action-btn[data-action="confirm_payment"]').forEach(function(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); const orderId=btn.dataset.orderId; const confirmMsg=btn.dataset.confirmMessage||'هل أنت متأكد؟'; if(!orderId) return; if(confirm(confirmMsg)){ fetch('/api/orders/confirm-payment',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({order_id:parseInt(orderId)})}).then(r=>r.json()).then(d=>{ if(d.success){ alert('تم بنجاح: '+d.message); window.location.reload(); } else { alert('خطأ: '+d.message); } }).catch(()=>alert('حدث خطأ فني')); }}); });
        document.querySelectorAll('.action-btn[data-action="confirm_delivery"]').forEach(function(btn){ btn.addEventListener('click', function(e){ e.preventDefault(); const orderId=btn.dataset.orderId; const confirmMsg=btn.dataset.confirmMessage||'هل أنت متأكد؟'; if(!orderId) return; if(confirm(confirmMsg)){ fetch('/api/orders/confirm-delivery',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({order_id:parseInt(orderId)})}).then(r=>r.json()).then(d=>{ if(d.success){ alert('تم بنجاح: '+d.message); window.location.reload(); } else { alert('خطأ: '+d.message); } }).catch(()=>alert('حدث خطأ فني')); }}); });
      });
      </script>
    </div>
  </div>
</div>
