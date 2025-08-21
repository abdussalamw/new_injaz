<?php
$page_title = 'معرض أنماط الأزرار التجريبية';
?>
<style>
.action-styles-wrapper{display:grid;gap:30px;margin-top:10px}
.action-style-block{background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:18px;box-shadow:0 2px 6px rgba(0,0,0,.05);position:relative}
.action-style-block h5{margin:0 0 12px;font-size:15px;font-weight:700;color:#D44759}
.sample-card{border:1px solid #ddd;border-radius:8px;padding:14px;position:relative;min-height:200px;display:flex;flex-direction:column}
.sample-card .body-text{font-size:13px;line-height:1.5;color:#444;margin-bottom:12px}
.sample-card .products{font-weight:600;margin-bottom:6px}
.variant-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:26px}
.sample-card .task-actions .btn{display:inline-flex;align-items:center;justify-content:center;width:100%;height:35px;font-size:12px;font-weight:500;gap:4px;transition:.25s}
.sample-card .task-actions .btn i{font-size:16px}
.sample-card .task-actions .btn:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(0,0,0,.15)}
/* 1 */
.action-style-1 .task-actions .actions-row{display:flex;flex-wrap:nowrap;gap:6px}
.action-style-1 .task-actions .action-cell{flex:1 1 0}
.action-style-1 .task-actions .btn{flex-direction:column;height:66px!important;font-size:11px}
.action-style-1 .task-actions .btn i{margin:0 0 4px 0!important;font-size:19px}
/* 2 */
.action-style-2 .task-actions .actions-row{display:flex;flex-wrap:wrap;gap:6px}
.action-style-2 .task-actions .action-cell{flex:1 1 calc(50% - 6px)}
/* نمط 2 (محدّث): كبسولات بإنحناء أصغر وألوان عكسية (لون في الحدود والنص – الخلفية بيضاء، مع تعبئة عند المرور) */
.action-style-2 .task-actions .btn{border-radius:12px;height:40px!important;font-size:12px;background:#fff!important;color:var(--c,#333)!important;border:1px solid var(--c,#ccc)!important;box-shadow:none}
.action-style-2 .task-actions .btn:hover{background:var(--c,#0d6efd)!important;color:#fff!important}
/* 3 */
.action-style-3 .task-actions .actions-row{display:flex;flex-wrap:wrap;gap:10px}
.action-style-3 .task-actions .action-cell{flex:0 0 auto}
.action-style-3 .task-actions .btn{width:46px;height:46px!important;padding:0;font-size:0}
.action-style-3 .task-actions .btn span.label{display:none}
.action-style-3 .task-actions .btn i{margin:0!important;font-size:22px}
/* 4 */
.action-style-4 .task-actions .actions-row{display:flex;flex-wrap:wrap;gap:8px}
.action-style-4 .task-actions .action-cell.primary{flex:1 1 auto}
.action-style-4 .task-actions .action-cell.primary .btn{height:42px!important}
.action-style-4 .task-actions .action-cell:not(.primary) .btn{width:42px;height:42px!important;padding:0;border-radius:50%;font-size:0}
.action-style-4 .task-actions .action-cell:not(.primary) .btn span.label{display:none}
.action-style-4 .task-actions .action-cell:not(.primary) .btn i{margin:0!important;font-size:20px}
/* 5 */
.action-style-5 .sample-card{padding-bottom:90px}
.action-style-5 .task-actions{position:absolute;left:0;right:0;bottom:0;background:#fafafa;border-top:1px solid #e7e7e7;padding:6px 10px}
.action-style-5 .task-actions .actions-row{display:flex;gap:6px}
.action-style-5 .task-actions .action-cell{flex:1 1 0}
.action-style-5 .task-actions .btn{height:36px!important;font-size:11px}
/* 6 */
.action-style-6 .task-actions .actions-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px}
.action-style-6 .task-actions .btn{height:64px!important;flex-direction:column;font-size:11px}
.action-style-6 .task-actions .btn i{margin:0 0 4px 0!important;font-size:20px}
/* remaining badge */
.task-actions .btn.payment-btn .icon-wrap{position:relative;display:inline-block;width:20px;height:20px;line-height:20px}
.task-actions .btn.payment-btn .icon-wrap i{font-size:19px;line-height:20px}
.task-actions .btn.payment-btn .icon-wrap .remain{position:absolute;top:50%;left:50%;transform:translate(-50%,-56%);font-size:8px;font-weight:600;color:#fff;pointer-events:none}
/* semantic colors */
.btn-details{--c:#0d6efd;background:var(--c);border-color:var(--c);color:#fff}
.btn-whatsapp{--c:#25D366;background:var(--c);border-color:var(--c);color:#fff}
.btn-payment{--c:#f7b731;background:var(--c);border-color:var(--c);color:#222}
.btn-status{--c:#17a2b8;background:var(--c);border-color:var(--c);color:#fff}
.btn-delivery{--c:#198754;background:var(--c);border-color:var(--c);color:#fff}
.task-actions .btn:active{transform:scale(.95)}
</style>
<div class="action-styles-wrapper">
  <div class="intro alert alert-info">جميع الأنماط الستة أمامك مباشرة بدون تنقل بين صفحات. اختر رقم الشكل أو اطلب دمج (مثال: تخطيط 1 + كبسولات ألوان 2). القيمة 245 مثال للمتبقي في الدفع.</div>
  <div class="variant-grid">
  <?php
    $variants=[
      1=>'النمط 1: شريط أفقي (أيقونة فوق النص)',
      2=>'النمط 2: كبسولات (Pills)',
      3=>'النمط 3: أيقونات فقط',
      4=>'النمط 4: زر رئيسي + دوائر',
      5=>'النمط 5: شريط سفلي',
      6=>'النمط 6: بلاطات مصغرة'
    ];
    foreach($variants as $num=>$title):
      $remaining_display='245';
  ?>
    <div class="action-style-block action-style-<?= $num ?>">
      <h5><?= htmlspecialchars($title) ?></h5>
      <!-- الحالة قبل أن يصبح الطلب جاهز للتسليم (لا يظهر زر استلام) -->
      <div class="sample-card mb-3">
        <div class="products">• تصميم شعار + مطبوعات</div>
        <div class="body-text">عميل: مؤسسة التجربة | حالة حالية: قيد التنفيذ (لا يظهر زر استلام بعد)</div>
        <div class="task-actions">
          <div class="actions-row">
            <div class="action-cell primary">
              <button type="button" class="btn btn-details btn-sm"><i class="bi bi-pencil-square"></i><span class="label">تفاصيل</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-whatsapp btn-sm"><i class="bi bi-whatsapp"></i><span class="label">واتساب</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-payment btn-sm payment-btn"><span class="icon-wrap"><i class="bi bi-cash-coin"></i><span class="remain"><?= $remaining_display ?></span></span><span class="label">دفع</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-status btn-sm"><i class="bi bi-arrow-repeat"></i><span class="label">حالة</span></button>
            </div>
          </div>
        </div>
        <small class="text-muted mt-2 d-block">قبل الجاهزية: مكان الزر الأخير مخصّص لقائمة تغيير الحالة.</small>
      </div>
      <!-- الحالة بعد أن يصبح الطلب جاهز للتسليم (زر استلام يحل محل زر الحالة) -->
      <div class="sample-card">
        <div class="products">• تصميم شعار + مطبوعات</div>
        <div class="body-text">عميل: مؤسسة التجربة | حالة حالية: جاهز للتسليم (زر استلام متاح)</div>
        <div class="task-actions">
          <div class="actions-row">
            <div class="action-cell primary">
              <button type="button" class="btn btn-details btn-sm"><i class="bi bi-pencil-square"></i><span class="label">تفاصيل</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-whatsapp btn-sm"><i class="bi bi-whatsapp"></i><span class="label">واتساب</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-payment btn-sm payment-btn"><span class="icon-wrap"><i class="bi bi-cash-coin"></i><span class="remain"><?= $remaining_display ?></span></span><span class="label">دفع</span></button>
            </div>
            <div class="action-cell">
              <button type="button" class="btn btn-delivery btn-sm"><i class="bi bi-check-circle"></i><span class="label">استلام</span></button>
            </div>
          </div>
        </div>
        <small class="text-muted mt-2 d-block">بعد الجاهزية: زر الاستلام يظهر ويأخذ نفس مكان زر الحالة.</small>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
<div class="alert alert-secondary mt-4 small">اكتب: أريد الشكل رقم (1..6) أو صف الدمج المطلوب بالتفصيل.</div>

<!-- تجارب الجدول الزمني للمراحل (Timeline Experiments) -->
<style>
.timeline-experiments-wrapper{margin-top:50px}
.timeline-experiments-wrapper h4{font-size:16px;font-weight:700;color:#444;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.timeline-variants{display:grid;gap:28px;grid-template-columns:repeat(auto-fill,minmax(420px,1fr))}
.timeline-style-block{background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:16px;box-shadow:0 2px 6px rgba(0,0,0,.05);position:relative}
.timeline-style-block h6{font-size:14px;font-weight:700;color:#D44759;margin:0 0 12px}
/* جدول زمني: نمط A (جدول أعمدة لكل زوج أحداث + مدة) */
table.timeline-A{width:100%;border-collapse:separate;border-spacing:0 6px;direction:rtl}
table.timeline-A td,table.timeline-A th{padding:6px 10px;vertical-align:top}
table.timeline-A .slot{background:#f8f9fa;border:1px solid #dedede;border-radius:8px;padding:6px 10px;min-height:86px;display:flex;flex-direction:column;justify-content:space-between}
table.timeline-A .slot.du{background:#fff4e0;border-color:#f3c680}
table.timeline-A .slot .events{font-size:11px;line-height:1.25;font-weight:600;color:#555;display:flex;flex-direction:column;gap:2px;margin-bottom:6px}
table.timeline-A .slot .events span{display:block;white-space:nowrap}
table.timeline-A .slot .dt{font-size:11px;color:#777;font-family:monospace;direction:ltr;text-align:left}
table.timeline-A .slot.duration-only{align-items:center;justify-content:center;gap:4px}
table.timeline-A .slot.duration-only .dur-title{font-size:11px;font-weight:600;color:#a05d00}
table.timeline-A .slot.duration-only .total-dur{font-size:15px;font-weight:700;color:#c56a00}
/* صف المراحل */
table.timeline-A .phases-row td{padding:0}
table.timeline-A .phase-bars{display:grid;grid-template-columns:repeat(3,1fr) 1fr;gap:8px;margin-top:4px}
table.timeline-A .phase-bar{background:linear-gradient(90deg,#e3f2ff,#c8e8ff);border:1px solid #b7dbf5;border-radius:6px;padding:6px 8px;display:flex;flex-direction:column;gap:4px;position:relative;min-height:72px}
table.timeline-A .phase-bar.exec{background:linear-gradient(90deg,#f3ffe3,#d7f5b7);border-color:#c7e9a6}
table.timeline-A .phase-bar.total{background:linear-gradient(90deg,#f4ecff,#e0d5ff);border-color:#d2c3ff}
table.timeline-A .phase-bar .phase-title{font-size:12px;font-weight:700;color:#333}
table.timeline-A .phase-bar .phase-dur{font-size:13px;font-weight:600;color:#111}
table.timeline-A .phase-bar .sub{font-size:11px;color:#666;line-height:1.2}
table.timeline-A .phase-bar.total .phase-dur{color:#6a34b7}
/* شريط بصري داخل كل مرحلة لتمثيل النسبة */
table.timeline-A .phase-bar .progress-line{height:5px;border-radius:3px;background:#d0e9fa;overflow:hidden;margin-top:auto;position:relative}
table.timeline-A .phase-bar.exec .progress-line{background:#e3f6d0}
table.timeline-A .phase-bar.total .progress-line{background:#eadfff}
table.timeline-A .phase-bar .progress-line span{display:block;height:100%;background:#0d6efd}
table.timeline-A .phase-bar.exec .progress-line span{background:#5dbb22}
table.timeline-A .phase-bar.total .progress-line span{background:#8845e6}
/* نمط B: بأسلوب مخطط أفقي مدمج */
.timeline-B{direction:rtl;display:flex;flex-direction:column;gap:14px}
.timeline-B .times-row{display:grid;grid-template-columns:repeat(3,1fr) 140px;gap:10px}
.timeline-B .pair{background:#f8f9fa;border:1px solid #dedede;border-radius:8px;padding:8px 10px;display:flex;flex-direction:column;gap:4px;min-height:96px}
.timeline-B .pair .labels{font-size:11px;font-weight:600;color:#555;line-height:1.25}
.timeline-B .pair .labels span{display:block}
.timeline-B .pair .time{font-size:11px;color:#777;font-family:monospace;direction:ltr;text-align:left;margin-top:auto}
.timeline-B .pair.duration{background:#fff4e0;border-color:#f3c680;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#c56a00}
.timeline-B .pair.simple{display:flex;flex-direction:column;justify-content:flex-start;gap:4px;padding-top:10px}
.timeline-B .pair.simple .event-label{font-size:12px;font-weight:700;color:#222;line-height:1}
.timeline-B .pair.simple .event-datetime{font-size:11px;color:#555;line-height:1.2;display:flex;flex-direction:column;align-items:flex-start;direction:ltr;font-family:monospace}
.timeline-B .pair.simple .event-datetime .date{font-weight:600;letter-spacing:.5px}
.timeline-B .pair.simple .event-datetime .time{font-size:11px;color:#666}
.timeline-B .bars{display:grid;grid-template-columns:repeat(3,1fr) 140px;gap:10px;align-items:stretch}
.timeline-B .bar{position:relative;background:#e3f2ff;border:1px solid #b7dbf5;border-radius:8px;padding:8px;display:flex;flex-direction:column;gap:4px;min-height:74px}
.timeline-B .bar.exec{background:#f3ffe3;border-color:#c7e9a6}
.timeline-B .bar.total{background:#f4ecff;border-color:#d2c3ff}
.timeline-B .bar .title{font-size:12px;font-weight:700;color:#333}
.timeline-B .bar .dur{font-size:14px;font-weight:600;color:#111}
.timeline-B .bar.total .dur{color:#6a34b7}
.timeline-B .bar .notes{font-size:11px;color:#666;line-height:1.2}
.timeline-B .bar .progress-line{height:6px;background:#d0e9fa;border-radius:4px;overflow:hidden;margin-top:auto}
.timeline-B .bar .progress-line span{display:block;height:100%;background:#0d6efd}
.timeline-B .bar.exec .progress-line{background:#e3f6d0}
.timeline-B .bar.exec .progress-line span{background:#5dbb22}
.timeline-B .bar.total .progress-line{background:#eadfff}
.timeline-B .bar.total .progress-line span{background:#8845e6}
.timeline-note{font-size:11px;color:#666;margin-top:8px;line-height:1.4}
/* شارة اختيار */
.timeline-style-block .choose-badge{position:absolute;top:10px;left:10px;background:#0d6efd;color:#fff;font-size:11px;padding:3px 8px;border-radius:20px;cursor:pointer;transition:.25s}
.timeline-style-block .choose-badge:hover{background:#0a58ca}
/* تحسين النمط B لداخل البطاقة: نسخة مضغوطة */
.timeline-B-compact{direction:rtl;display:flex;flex-direction:column;gap:6px;font-size:11px}
.timeline-B-compact .header-line{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:4px;background:#f8f9fa;border:1px solid #e1e5e9;border-radius:8px;padding:6px 10px}
.timeline-B-compact .header-line .time-pair{display:flex;align-items:center;gap:4px;color:#555}
.timeline-B-compact .header-line .time-pair span.label{font-weight:600;color:#333}
.timeline-B-compact .header-line .time-pair.stacked{display:flex;flex-direction:column;align-items:flex-start;padding:0 2px 0 2px}
.timeline-B-compact .header-line .time-pair.stacked .label{font-size:11px;color:#222;margin-bottom:2px;font-weight:700}
.timeline-B-compact .header-line .time-pair.stacked .date-time{font-size:10px;font-family:monospace;font-weight:600;color:#444;line-height:1.1}
.timeline-B-compact .header-line .time-pair.inline-mid{display:flex;align-items:center;gap:4px}
.timeline-B-compact .header-line .time-pair.inline-mid .date-time{font-family:monospace;font-size:11px;color:#555}
.timeline-B-compact .header-line .total{font-weight:700;color:#6a34b7}
.timeline-B-compact .phase-bars{display:flex;gap:6px;align-items:stretch}
.timeline-B-compact .phase{flex:1;display:flex;flex-direction:column;gap:4px;min-width:0;background:#eef6ff;border:1px solid #d3e6f7;padding:6px 6px 8px;border-radius:6px;position:relative}
.timeline-B-compact .phase.exec{background:#f2ffe8;border-color:#d7f3c0}
.timeline-B-compact .phase.total{background:#f4ecff;border-color:#e1d6ff}
.timeline-B-compact .phase .ph-title{font-size:11px;font-weight:600;color:#222;margin:0;display:flex;justify-content:space-between;gap:4px}
.timeline-B-compact .phase.total .ph-title{color:#5a299f}
.timeline-B-compact .phase .dur{font-size:11px;font-weight:600;color:#111}
.timeline-B-compact .phase .period{font-size:10px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.timeline-B-compact .phase .period{display:none !important}
.timeline-B-compact .phase .progress{height:5px;background:#d2e9fb;border-radius:3px;overflow:hidden;margin-top:auto;position:relative}
.timeline-B-compact .phase.exec .progress{background:#e0f5cc}
.timeline-B-compact .phase.total .progress{background:#e9defd}
.timeline-B-compact .phase .progress span{display:block;height:100%;background:#0d6efd}
.timeline-B-compact .phase.exec .progress span{background:#55b61d}
.timeline-B-compact .phase.total .progress span{background:#8845e6}
.timeline-B-compact.compact-xs .header-line{flex-direction:column;align-items:flex-start}
.timeline-B-compact.compact-xs .phase-bars{flex-direction:column}
.timeline-B-compact .phase .badges{position:absolute;top:4px;left:4px;display:flex;gap:3px}
.timeline-B-compact .phase .badge-mini{background:#fff;border:1px solid #ccc;font-size:9px;padding:1px 4px;border-radius:10px;color:#555}
</style>
<div class="timeline-experiments-wrapper">
  <h4><i class="bi bi-clock-history text-primary"></i> تجارب عرض الجدول الزمني للمراحل</h4>
  <div class="timeline-variants">
    <!-- نمط A -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-A">اختر A</div>
      <h6>النمط A: جدول ثنائي الأسطر للأحداث + صف مراحل منفصل</h6>
      <table class="timeline-A">
        <tr class="times-row">
          <td>
            <div class="slot">
              <div class="events"><span>بداية المهمة</span><span>بداية التصميم</span></div>
              <div class="dt">2025-08-18 09:15</div>
            </div>
          </td>
          <td>
            <div class="slot">
              <div class="events"><span>نهاية التصميم</span><span>بداية التنفيذ</span></div>
              <div class="dt">2025-08-18 13:45</div>
            </div>
          </td>
          <td>
            <div class="slot">
              <div class="events"><span>نهاية التنفيذ</span><span>انتهاء المهمة</span></div>
              <div class="dt">2025-08-19 15:10</div>
            </div>
          </td>
          <td>
            <div class="slot duration-only du">
              <div class="dur-title">المدة الكاملة</div>
              <div class="total-dur">1ي 5س 55د</div>
            </div>
          </td>
        </tr>
        <tr class="phases-row">
          <td colspan="4">
            <div class="phase-bars">
              <div class="phase-bar design">
                <div class="phase-title">مرحلة التصميم</div>
                <div class="phase-dur">4س 30د</div>
                <div class="sub">من 09:15 إلى 13:45</div>
                <div class="progress-line"><span style="width:35%"></span></div>
              </div>
              <div class="phase-bar exec">
                <div class="phase-title">مرحلة التنفيذ</div>
                <div class="phase-dur">1ي 1س 25د</div>
                <div class="sub">من 13:45 إلى 15:10 (+اليوم التالي)</div>
                <div class="progress-line"><span style="width:65%"></span></div>
              </div>
              <div class="phase-bar total">
                <div class="phase-title">المهمة كاملة</div>
                <div class="phase-dur">1ي 5س 55د</div>
                <div class="sub">09:15 → 15:10 (اليوم التالي)</div>
                <div class="progress-line"><span style="width:100%"></span></div>
              </div>
              <div class="phase-bar total" style="opacity:.35;display:flex;align-items:center;justify-content:center">
                <div style="font-size:11px;color:#555;text-align:center;padding:4px 2px">(عمود احتياطي فارغ يمكن استخدامه لتوسع لاحق)</div>
              </div>
            </div>
          </td>
        </tr>
      </table>
      <div class="timeline-note">يوضح الصف الأول الأزواج الزمنية (حدث بداية وحدث نهاية/بداية تالية). الصف الثاني يوضح كل مرحلة ومدتها مع نسبة تقدم رسومية.</div>
    </div>
    <!-- نمط B -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-B">اختر B</div>
      <h6>النمط B: بطاقات أفقية مدمجة (أعلى أزمنة – أسفل مراحل)</h6>
      <div class="timeline-B">
        <div class="times-row">
          <div class="pair simple">
            <div class="event-label">بدء التصميم</div>
            <div class="event-datetime">
              <span class="date">2025-08-18</span>
              <span class="time">09:15</span>
            </div>
          </div>
          <div class="pair">
            <div class="labels"><span>نهاية التصميم</span><span>بداية التنفيذ</span></div>
            <div class="time">2025-08-18 13:45</div>
          </div>
            <div class="pair">
              <div class="labels"><span>نهاية التنفيذ</span><span>انتهاء المهمة</span></div>
              <div class="time">2025-08-19 15:10</div>
            </div>
          <div class="pair duration">1ي 5س 55د</div>
        </div>
        <div class="bars">
          <div class="bar design">
            <div class="title">مرحلة التصميم</div>
            <div class="dur">4س 30د</div>
            <div class="notes">09:15 → 13:45</div>
            <div class="progress-line"><span style="width:35%"></span></div>
          </div>
          <div class="bar exec">
            <div class="title">مرحلة التنفيذ</div>
            <div class="dur">1ي 1س 25د</div>
            <div class="notes">13:45 → 15:10 (+يوم)</div>
            <div class="progress-line"><span style="width:65%"></span></div>
          </div>
          <div class="bar total">
            <div class="title">المهمة كاملة</div>
            <div class="dur">1ي 5س 55د</div>
            <div class="notes">09:15 → 15:10 (اليوم التالي)</div>
            <div class="progress-line"><span style="width:100%"></span></div>
          </div>
          <div class="bar total" style="opacity:.35;display:flex;align-items:center;justify-content:center">
            <div style="font-size:11px;color:#555;text-align:center">احتياط</div>
          </div>
        </div>
      </div>
      <div class="timeline-note">النمط B أكثر مرونة في الشاشات الضيقة (يتحول لصفوف مكدسة بسهولة لاحقاً) ويمكن دمجه أعلى أو أسفل أزرار الإجراءات.</div>
    </div>
    <!-- نسخة محسّنة مضغوطة للنمط B للاستخدام داخل البطاقة مباشرة -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-B-compact">اختر B-Compact</div>
      <h6>النمط B-Compact: نسخة مضغوطة مناسبة مباشرة لداخل البطاقة</h6>
      <div class="timeline-B-compact">
        <div class="header-line">
          <div class="time-pair stacked">
            <span class="label">بدء التصميم</span>
            <span class="date-time">2025-08-18 09:15</span>
          </div>
          <div class="time-pair stacked">
            <span class="label">انتهاء التصميم</span>
            <span class="date-time">2025-08-18 13:45</span>
          </div>
          <div class="time-pair stacked">
            <span class="label">انتهاء التنفيذ</span>
            <span class="date-time">2025-08-19 15:10</span>
          </div>
        </div>
        <div class="phase-bars">
          <div class="phase design">
            <div class="ph-title">تصميم <span class="dur">4س 30د</span></div>
            <div class="period">09:15 → 13:45</div>
            <div class="progress"><span style="width:35%"></span></div>
          </div>
          <div class="phase exec">
            <div class="ph-title">تنفيذ <span class="dur">1ي 1س 25د</span></div>
            <div class="period">13:45 → 15:10 (+يوم)</div>
            <div class="progress"><span style="width:65%"></span></div>
          </div>
          <div class="phase total">
            <div class="ph-title">المهمة <span class="dur">1ي 5س 55د</span></div>
            <div class="period">09:15 → 15:10 (اليوم التالي)</div>
            <div class="progress"><span style="width:100%"></span></div>
          </div>
        </div>
      </div>
      <div class="timeline-note">تعرض السطر العلوي أهم الطوابع الزمنية (بداية / نهاية تصميم / نهاية تنفيذ) ثم ثلاث أعمدة مراحل مختصرة. يمكن تقليصها أكثر (وضع عمودي) للشاشات الأصغر.</div>
    </div>
  </div>
    <!-- نمط C: شريط مرحلي مدمج (Integrated Phase Strip) -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-C">اختر C</div>
      <h6>النمط C: شريط مرحلي مدمج (مضغوط لداخل البطاقة)</h6>
      <div class="timeline-C">
        <div class="phase-strip">
          <div class="phase-step design completed">
            <div class="step-icon">✓</div>
            <div class="step-content">
              <div class="step-title">تصميم</div>
              <div class="step-time">4س 30د</div>
            </div>
          </div>
          <div class="phase-step execution active">
            <div class="step-icon">⏳</div>
            <div class="step-content">
              <div class="step-title">تنفيذ</div>
              <div class="step-time">1ي 1س</div>
            </div>
          </div>
          <div class="phase-step delivery pending">
            <div class="step-icon">📦</div>
            <div class="step-content">
              <div class="step-title">تسليم</div>
              <div class="step-time">انتظار</div>
            </div>
          </div>
        </div>
        <div class="overall-progress">
          <div class="progress-label">التقدم الإجمالي: 1ي 5س 55د</div>
          <div class="progress-bar-container">
            <div class="progress-bar" style="width:65%"></div>
          </div>
        </div>
      </div>
      <div class="timeline-note">مدمج ومضغوط، يوضح المراحل الثلاث (مكتمل/نشط/انتظار) مع شريط تقدم إجمالي أسفل. مناسب للبطاقات الصغيرة.</div>
    </div>

    <!-- نمط D: أيقونات دائرية متصلة (Connected Circle Timeline) -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-D">اختر D</div>
      <h6>النمط D: أيقونات دائرية متصلة بخط زمني</h6>
      <div class="timeline-D">
        <div class="circle-timeline">
          <div class="timeline-item completed">
            <div class="circle-icon">📝</div>
            <div class="item-details">
              <div class="item-title">بدء التصميم</div>
              <div class="item-time">18/8 09:15</div>
            </div>
          </div>
          <div class="timeline-connector completed"></div>
          <div class="timeline-item completed">
            <div class="circle-icon">✅</div>
            <div class="item-details">
              <div class="item-title">انتهاء التصميم</div>
              <div class="item-time">18/8 13:45</div>
            </div>
          </div>
          <div class="timeline-connector active"></div>
          <div class="timeline-item active">
            <div class="circle-icon">⚡</div>
            <div class="item-details">
              <div class="item-title">قيد التنفيذ</div>
              <div class="item-time">جاري...</div>
            </div>
          </div>
          <div class="timeline-connector pending"></div>
          <div class="timeline-item pending">
            <div class="circle-icon">📋</div>
            <div class="item-details">
              <div class="item-title">جاهز للتسليم</div>
              <div class="item-time">متوقع: 19/8</div>
            </div>
          </div>
        </div>
      </div>
      <div class="timeline-note">خط زمني تفاعلي بأيقونات ملونة وخطوط واصلة، يوضح الحالة الحالية والمتوقعة. بصري وأنيق.</div>
    </div>

    <!-- نمط E: كروت مصغرة متراصة (Mini Card Stack) -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-E">اختر E</div>
      <h6>النمط E: كروت مصغرة متراصة (عمودي مضغوط)</h6>
      <div class="timeline-E">
        <div class="mini-cards">
          <div class="mini-card design done">
            <div class="card-header">
              <span class="phase-name">تصميم</span>
              <span class="status-badge">✓ مكتمل</span>
            </div>
            <div class="card-body">
              <div class="duration">4س 30د</div>
              <div class="period">09:15 - 13:45</div>
            </div>
          </div>
          <div class="mini-card execution current">
            <div class="card-header">
              <span class="phase-name">تنفيذ</span>
              <span class="status-badge">⏳ جاري</span>
            </div>
            <div class="card-body">
              <div class="duration">1ي 1س حتى الآن</div>
              <div class="period">13:45 - جاري</div>
            </div>
          </div>
          <div class="mini-card delivery waiting">
            <div class="card-header">
              <span class="phase-name">تسليم</span>
              <span class="status-badge">⏸ انتظار</span>
            </div>
            <div class="card-body">
              <div class="duration">لم يبدأ</div>
              <div class="period">متوقع: 19/8</div>
            </div>
          </div>
        </div>
        <div class="summary-line">إجمالي: 1ي 5س 55د • التقدم: 65%</div>
      </div>
      <div class="timeline-note">كروت مصغرة متراصة عمودياً، كل كرت يوضح مرحلة واحدة بتفاصيلها. مناسب للعرض الجانبي في البطاقة.</div>
    </div>

    <!-- نمط F: شريط تقدم ذكي مع تفاصيل منبثقة (Smart Progress Bar) -->
    <div class="timeline-style-block">
      <div class="choose-badge" data-choose="timeline-F">اختر F</div>
      <h6>النمط F: شريط تقدم ذكي بتقسيمات (هوفر للتفاصيل)</h6>
      <div class="timeline-F">
        <div class="smart-progress">
          <div class="progress-segments">
            <div class="segment design completed" data-tooltip="تصميم: 4س 30د (09:15-13:45)">
              <div class="segment-fill"></div>
              <div class="segment-label">تصميم</div>
            </div>
            <div class="segment execution active" data-tooltip="تنفيذ: 1ي 1س حتى الآن (13:45-جاري)">
              <div class="segment-fill"></div>
              <div class="segment-label">تنفيذ</div>
            </div>
            <div class="segment delivery pending" data-tooltip="تسليم: لم يبدأ (متوقع 19/8)">
              <div class="segment-fill"></div>
              <div class="segment-label">تسليم</div>
            </div>
          </div>
          <div class="progress-info">
            <div class="current-phase">المرحلة الحالية: تنفيذ (65% مكتمل)</div>
            <div class="total-time">الوقت الإجمالي: 1ي 5س 55د</div>
          </div>
        </div>
      </div>
      <div class="timeline-note">شريط تقدم مقسم لثلاث مراحل مع ألوان تدرجية، عند المرور بالماوس تظهر تفاصيل كل مرحلة. مضغوط جداً.</div>
    </div>

  <div class="alert alert-info small mt-3">اختر أي نمط (A-F) أو اطلب دمج/تعديل. النماذج C-F مصممة خصيصاً لتكون مدمجة ضمن البطاقة بمساحة أقل.</div>
</div>

<style>
/* نمط C: شريط مرحلي مدمج */
.timeline-C{padding:8px;background:#f8f9fa;border-radius:8px;direction:rtl}
.timeline-C .phase-strip{display:flex;gap:12px;margin-bottom:10px}
.timeline-C .phase-step{display:flex;align-items:center;gap:6px;flex:1;padding:6px;border-radius:6px;position:relative}
.timeline-C .phase-step.completed{background:#d4edda;border:1px solid #c3e6cb}
.timeline-C .phase-step.active{background:#fff3cd;border:1px solid #ffeaa7}
.timeline-C .phase-step.pending{background:#f8d7da;border:1px solid #f5c6cb}
.timeline-C .step-icon{font-size:16px;min-width:20px;text-align:center}
.timeline-C .step-content{display:flex;flex-direction:column;gap:2px}
.timeline-C .step-title{font-size:11px;font-weight:600;color:#333}
.timeline-C .step-time{font-size:10px;color:#666}
.timeline-C .overall-progress{display:flex;flex-direction:column;gap:4px}
.timeline-C .progress-label{font-size:11px;font-weight:600;color:#555}
.timeline-C .progress-bar-container{height:6px;background:#e9ecef;border-radius:3px;overflow:hidden}
.timeline-C .progress-bar{height:100%;background:linear-gradient(90deg,#28a745,#20c997);transition:.3s}

/* نمط D: دوائر متصلة */
.timeline-D{direction:rtl;padding:8px}
.timeline-D .circle-timeline{display:flex;align-items:center;gap:8px}
.timeline-D .timeline-item{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1}
.timeline-D .circle-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600}
.timeline-D .timeline-item.completed .circle-icon{background:#28a745;color:#fff}
.timeline-D .timeline-item.active .circle-icon{background:#ffc107;color:#333}
.timeline-D .timeline-item.pending .circle-icon{background:#6c757d;color:#fff}
.timeline-D .item-details{text-align:center}
.timeline-D .item-title{font-size:11px;font-weight:600;color:#333}
.timeline-D .item-time{font-size:10px;color:#666}
.timeline-D .timeline-connector{flex:0 0 20px;height:3px;border-radius:2px}
.timeline-D .timeline-connector.completed{background:#28a745}
.timeline-D .timeline-connector.active{background:linear-gradient(90deg,#28a745,#ffc107)}
.timeline-D .timeline-connector.pending{background:#dee2e6}

/* نمط E: كروت مصغرة */
.timeline-E{direction:rtl;padding:8px}
.timeline-E .mini-cards{display:flex;flex-direction:column;gap:6px;margin-bottom:8px}
.timeline-E .mini-card{border-radius:6px;padding:6px 8px;border:1px solid #dee2e6}
.timeline-E .mini-card.done{background:#d4edda;border-color:#c3e6cb}
.timeline-E .mini-card.current{background:#fff3cd;border-color:#ffeaa7}
.timeline-E .mini-card.waiting{background:#f8d7da;border-color:#f5c6cb}
.timeline-E .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px}
.timeline-E .phase-name{font-size:12px;font-weight:600;color:#333}
.timeline-E .status-badge{font-size:10px;padding:2px 6px;border-radius:10px;background:#fff;border:1px solid #ccc}
.timeline-E .card-body{display:flex;justify-content:space-between;align-items:center}
.timeline-E .duration{font-size:11px;font-weight:600;color:#555}
.timeline-E .period{font-size:10px;color:#777}
.timeline-E .summary-line{font-size:11px;font-weight:600;color:#495057;text-align:center;padding:4px;background:#e9ecef;border-radius:4px}

/* نمط F: شريط ذكي */
.timeline-F{direction:rtl;padding:8px}
.timeline-F .progress-segments{display:flex;border-radius:6px;overflow:hidden;border:1px solid #dee2e6;margin-bottom:8px}
.timeline-F .segment{flex:1;position:relative;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.25s}
.timeline-F .segment.completed{background:#28a745}
.timeline-F .segment.active{background:linear-gradient(90deg,#ffc107,#fd7e14)}
.timeline-F .segment.pending{background:#6c757d}
.timeline-F .segment-label{font-size:11px;font-weight:600;color:#fff;position:relative;z-index:2}
.timeline-F .segment-fill{position:absolute;top:0;left:0;right:0;bottom:0;opacity:.8}
.timeline-F .segment.completed .segment-fill{background:#155724}
.timeline-F .segment.active .segment-fill{background:repeating-linear-gradient(45deg,transparent,transparent 3px,rgba(255,255,255,.1) 3px,rgba(255,255,255,.1) 6px)}
.timeline-F .progress-info{display:flex;justify-content:space-between;align-items:center}
.timeline-F .current-phase{font-size:11px;font-weight:600;color:#495057}
.timeline-F .total-time{font-size:10px;color:#6c757d}
.timeline-F .segment:hover{transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,.15)}
</style>
