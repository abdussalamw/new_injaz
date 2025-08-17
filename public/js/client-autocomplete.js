
// Client Autocomplete Logic - محسن للبحث الديناميكي
document.addEventListener('DOMContentLoaded', function() {
    const companyInput = document.getElementById('company_name_input');
    const phoneInput = document.getElementById('phone_input');
    const clientIdHidden = document.getElementById('client_id_hidden');
    const autocompleteList = document.getElementById('autocomplete-list');
    const phoneLock = document.getElementById('phone_lock');

    let searchTimeout;

    function performSearch() {
        const query = companyInput.value.trim();

        // إعادة تعيين القيم عند تغيير البحث
        if (!clientIdHidden.value || companyInput.value !== companyInput.dataset.selectedValue) {
            clientIdHidden.value = '';
            phoneInput.readOnly = false;
            companyInput.dataset.selectedValue = '';
        }

        if (query.length < 2) {  // تغيير إلى حرفين على الأقل للبحث
            autocompleteList.innerHTML = '';
            return;
        }

        // إظهار مؤشر التحميل
        autocompleteList.innerHTML = '<span class="list-group-item text-muted"><i class="fas fa-spinner fa-spin me-2"></i>جاري البحث...</span>';

        // تحديد المسار الصحيح حسب البيئة
        const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        const apiPath = isLocal ? '/api/clients/search' : '<?= $_ENV['BASE_PATH'] ?>/api/clients/search';

        fetch(`${apiPath}?query=${encodeURIComponent(query)}`, {
                credentials: 'include',  // إرسال الكوكيز مع الطلب
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                // التحقق من إعادة التوجيه للتسجيل
                if (response.redirected && response.url.includes('/login')) {
                    throw new Error('AUTHENTICATION_REQUIRED');
                }

                // التحقق من رمز الحالة 401 (غير مصرح)
                if (response.status === 401) {
                    throw new Error('AUTHENTICATION_REQUIRED');
                }

                if (!response.ok) {
                    return response.text().then(text => {
                        console.log('Server Error Response:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.log('Non-JSON Response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }

                return response.json();
            })
            .then(clients => {
                autocompleteList.innerHTML = '';

                if (clients.error) {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-danger text-center py-3">
                            <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">خطأ: ${clients.message}</p>
                        </div>
                    `;
                    return;
                }

                if (clients.length > 0) {
                    clients.forEach((client, index) => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.classList.add('list-group-item', 'list-group-item-action', 'd-flex', 'justify-content-between', 'align-items-start');
                        item.style.transition = 'all 0.3s ease';

                        // تحسين عرض النتائج
                        const mainDiv = document.createElement('div');
                        mainDiv.classList.add('flex-grow-1');

                        const companyName = document.createElement('h6');
                        companyName.classList.add('mb-1', 'fw-bold', 'text-dark');
                        companyName.textContent = client.company_name;
                        mainDiv.appendChild(companyName);

                        const phoneDiv = document.createElement('small');
                        phoneDiv.classList.add('text-success', 'd-flex', 'align-items-center');
                        phoneDiv.innerHTML = `<i class="fas fa-phone me-1"></i>${client.phone}`;

                        // إضافة مؤشر بصري للاختيار
                        const selectIcon = document.createElement('i');
                        selectIcon.classList.add('fas', 'fa-chevron-left', 'text-muted', 'ms-2');

                        item.appendChild(mainDiv);
                        item.appendChild(phoneDiv);
                        item.appendChild(selectIcon);

                        // تأثير hover
                        item.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = '#f8f9fa';
                            selectIcon.style.color = '#007bff';
                        });

                        item.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = '';
                            selectIcon.style.color = '';
                        });

                        item.addEventListener('click', function(e) {
                            e.preventDefault();

                            // تأثير الاختيار
                            this.style.backgroundColor = '#d4edda';

                            // ملء البيانات تلقائياً
                            companyInput.value = client.company_name;
                            companyInput.dataset.selectedValue = client.company_name;
                            phoneInput.value = client.phone;
                            clientIdHidden.value = client.client_id;

                            // منع التعديل على البيانات المحددة مسبقاً
                            phoneInput.readOnly = true;

                            // إضافة مؤشر بصري للحقول المقفلة
                            phoneInput.classList.add('bg-light');
                            phoneLock.style.display = 'block';

                            autocompleteList.innerHTML = '';

                            // عرض رسالة تأكيد
                            showClientSelectedMessage(client.company_name);
                        });

                        autocompleteList.appendChild(item);
                    });

                    // إضافة خيار إنشاء عميل جديد
                    const newClientItem = document.createElement('a');
                    newClientItem.href = '#';
                    newClientItem.classList.add('list-group-item', 'list-group-item-action', 'text-primary', 'fw-bold', 'border-top');
                    newClientItem.style.backgroundColor = '#f8f9fa';
                    newClientItem.innerHTML = '<i class="fas fa-plus me-2"></i>إنشاء جهة جديدة بهذا الاسم';
                    newClientItem.addEventListener('click', function(e) {
                        e.preventDefault();
                        clearClientSelection();
                        autocompleteList.innerHTML = '';
                    });
                    autocompleteList.appendChild(newClientItem);

                } else {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-center py-3">
                            <i class="fas fa-info-circle text-primary mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-1 text-muted">لا توجد جهات مطابقة</p>
                            <small class="text-muted">سيتم إنشاء جهة جديدة باسم: <strong>${query}</strong></small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.log('Search Error:', error.message);

                if (error.message === 'AUTHENTICATION_REQUIRED') {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-center py-3 text-warning">
                            <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-1">انتهت جلسة العمل</p>
                            <small>الرجاء <a href="<?= $_ENV['BASE_PATH'] ?>/login" class="text-primary">تسجيل الدخول</a> مرة أخرى</small>
                        </div>
                    `;
                } else {
                    autocompleteList.innerHTML = `
                        <div class="list-group-item text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">حدث خطأ في البحث</p>
                            <small>الرجاء المحاولة مرة أخرى أو تحديث الصفحة</small>
                        </div>
                    `;
                }
            });
    }

    function clearClientSelection() {
        clientIdHidden.value = '';
        phoneInput.readOnly = false;
        phoneInput.classList.remove('bg-light');
        phoneLock.style.display = 'none';
        companyInput.dataset.selectedValue = '';
        hideClientSelectedMessage();

        // إضافة تأثير بصري لإعادة التفعيل
        phoneInput.style.transition = 'all 0.3s ease';
        phoneInput.style.borderColor = '#007bff';
        setTimeout(() => {
            phoneInput.style.borderColor = '';
        }, 1000);
    }

    function showClientSelectedMessage(companyName) {
        // إزالة الرسالة السابقة إن وجدت
        hideClientSelectedMessage();

        const messageDiv = document.createElement('div');
        messageDiv.id = 'client-selected-message';
        messageDiv.classList.add('alert', 'alert-success', 'alert-dismissible', 'fade', 'show', 'mt-2', 'border-0');
        messageDiv.style.borderLeft = '4px solid #28a745';
        messageDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2 text-success"></i>
                <div class="flex-grow-1">
                    <strong>تم اختيار الجهة:</strong> ${companyName}
                    <br><small class="text-muted">البيانات محفوظة ومحمية من التعديل</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearClientSelection()">
                    <i class="fas fa-edit me-1"></i>تعديل البيانات
                </button>
            </div>
        `;

        companyInput.parentNode.appendChild(messageDiv);

        // تأثير ظهور مع تأخير
        setTimeout(() => {
            messageDiv.classList.add('show');
        }, 100);
    }

    function hideClientSelectedMessage() {
        const messageDiv = document.getElementById('client-selected-message');
        if (messageDiv) {
            messageDiv.remove();
        }
    }

    // جعل clearClientSelection متاحة عالمياً
    window.clearClientSelection = clearClientSelection;

    // البحث عند الكتابة مع تأخير
    companyInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300); // تأخير 300ms
    });

    // البحث الفوري عند التركيز إذا كان هناك نص
    companyInput.addEventListener('focus', function() {
        if (this.value.length > 0 && !clientIdHidden.value) {
            performSearch();
        }
    });

    // إخفاء القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!companyInput.contains(e.target) && !autocompleteList.contains(e.target)) {
            autocompleteList.innerHTML = '';
        }
    });

    // تنظيف البيانات عند تعديل اسم الجهة يدوياً
    companyInput.addEventListener('input', function() {
        if (this.value !== this.dataset.selectedValue && clientIdHidden.value) {
            clearClientSelection();
        }
    });
});
