/**
 * Reco Common JavaScript Functions
 *
 * Reusable AJAX handlers and utility functions.
 */

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json'
    }
});

$(document).ajaxError(function(event, xhr, settings) {
    if (settings && settings.suppressGlobalErrorHandler) {
        return;
    }

    if (xhr.status === 401 || xhr.status === 419) {
        toastr.warning('Your session has expired. Redirecting to login...');
        setTimeout(function() {
            window.location.href = '/admin/login?expired=1';
        }, 1500);
    }
});

function getFormSelector(formId) {
    return formId.startsWith('#') ? formId : `#${formId}`;
}

function resetSubmitButton(submitBtn) {
    if (!submitBtn || submitBtn.length === 0) {
        return;
    }

    const originalBtnText = submitBtn.data('original-html');

    if (originalBtnText !== undefined) {
        submitBtn.html(originalBtnText);
    }

    submitBtn.prop('disabled', false);
}

function getValidationContainer(input) {
    if (input.closest('.input-group').length) {
        return input.closest('.input-group');
    }

    if (input.closest('.form-check').length) {
        return input.closest('.form-check');
    }

    if (input.hasClass('select2-hidden-accessible')) {
        const select2Container = input.next('.select2-container');
        if (select2Container.length) {
            return select2Container;
        }
    }

    return input;
}

function normalizeFieldSelector(field) {
    if (field.indexOf('.') === -1) {
        return field;
    }

    // Convert dot notation (lines.0.quantity) to bracket notation (lines[0][quantity])
    const parts = field.split('.');
    let result = parts[0];
    for (let i = 1; i < parts.length; i++) {
        result += '[' + parts[i] + ']';
    }
    return result;
}

function ajaxFormSubmit(formId, url, method, successCallback, errorCallback = null) {
    const selector = getFormSelector(formId);
    const form = $(selector);

    if (form.length === 0) {
        console.error('Form not found:', formId);
        return;
    }

    form.attr('novalidate', 'novalidate');

    form.off('submit.ajaxFormSubmit').on('submit.ajaxFormSubmit', function(e) {
        e.preventDefault();

        const submitBtn = form.find('button[type="submit"], input[type="submit"]').first();
        if (submitBtn.length && submitBtn.data('original-html') === undefined) {
            submitBtn.data('original-html', submitBtn.html());
        }

        clearValidationErrors(formId);

        if (submitBtn.length) {
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        }

        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            suppressGlobalErrorHandler: true,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Operation successful!');

                    if (typeof successCallback === 'function') {
                        successCallback(response);
                    } else if (typeof successCallback === 'string' && successCallback.length > 0) {
                        setTimeout(function() {
                            window.location.href = successCallback;
                        }, 800);
                    }
                } else {
                    toastr.error(response.message || 'Something went wrong!');
                }
            },
            error: function(xhr) {
                let response = xhr.responseJSON;
                if (!response && xhr.responseText) {
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response = null;
                    }
                }

                if (xhr.status === 422 && response && response.errors) {
                    const rendered = showValidationErrors(formId, response.errors);
                    if (!rendered) {
                        toastr.error(response.message || 'Please correct the errors below.');
                    }
                } else if (xhr.status === 401 || xhr.status === 419) {
                    toastr.error('Session expired. Please login again.');
                    setTimeout(function() {
                        window.location.href = '/admin/login?expired=1';
                    }, 1500);
                } else {
                    toastr.error(response?.message || 'An error occurred. Please try again.');
                }

                if (typeof errorCallback === 'function') {
                    errorCallback(xhr);
                }
            },
            complete: function() {
                resetSubmitButton(submitBtn);
            }
        });
    });
}

function initAjaxForms() {
    $('form[data-ajax="true"]').each(function() {
        const form = $(this);
        const formId = form.attr('id');
        const action = form.attr('action');
        const successRedirect = form.data('success-redirect') || null;
        let method = (form.find('input[name="_method"]').val() || form.attr('method') || 'POST').toUpperCase();

        if (!formId || !action) {
            return;
        }

        ajaxFormSubmit(formId, action, method, successRedirect);
    });
}

function deleteRecord(url, itemName, successCallback = null, redirectUrl = null) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete this ${itemName}? This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message || `${itemName} has been deleted.`, 'success');

                        if (typeof successCallback === 'function') {
                            successCallback(response);
                        }

                        if (redirectUrl) {
                            setTimeout(function() {
                                window.location.href = redirectUrl;
                            }, 1500);
                        }
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to delete.', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            });
        }
    });
}

function changeStatus(url, currentStatus, itemName, successCallback = null) {
    const action = currentStatus ? 'deactivate' : 'activate';

    Swal.fire({
        title: 'Are you sure?',
        text: `You want to ${action} this ${itemName}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${action} it!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'PATCH',
                data: {
                    status: currentStatus ? 'inactive' : 'active'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Updated!', response.message || `${itemName} has been ${action}d.`, 'success');

                        if (typeof successCallback === 'function') {
                            successCallback(response);
                        }
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to update status.', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            });
        }
    });
}

function formatDateIst(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'string' && /[A-Za-z]{3}/.test(value) && !value.includes('T')) {
        return value;
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return String(value);
    }

    return parsed.toLocaleDateString('en-IN', {
        timeZone: 'Asia/Kolkata',
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function formatDateTimeIst(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'string' && /[A-Za-z]{3}/.test(value) && !value.includes('T')) {
        return value;
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return String(value);
    }

    return parsed.toLocaleString('en-IN', {
        timeZone: 'Asia/Kolkata',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

function istDateColumn(dataKey, options = {}) {
    return $.extend({
        data: dataKey,
        render: function(data) {
            return formatDateIst(data);
        }
    }, options);
}

function datatableExportableColumn(columnIdx, data, node) {
    if (!node) {
        return true;
    }

    const header = $(node);
    const text = (header.text() || '').trim().toLowerCase();

    if (header.hasClass('no-export')) {
        return false;
    }

    // Avoid exporting action/empty columns by default.
    if (!text || text.includes('action')) {
        return false;
    }

    return true;
}

function buildPdfButtonConfig(overrides = {}) {
    const AUTO_PORTRAIT_MAX_COLUMNS = 6;

    const baseConfig = {
        extend: 'pdfHtml5',
        text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
        className: 'btn btn-danger btn-sm dt-export-btn dt-export-pdf',
        exportOptions: {
            columns: datatableExportableColumn
        },
        orientation: 'portrait',
        pageSize: 'A4',
        title: (document.title || 'Reco Export').replace(/^Reco\s*-\s*/i, ''),
        customize: function(doc) {
            // Keep margins tight so the table can occupy most of the page width.
            doc.pageMargins = [12, 16, 12, 16];
            doc.defaultStyle.fontSize = 8;
            doc.styles.title = {
                fontSize: 14,
                bold: true,
                color: '#1f2937',
                alignment: 'left',
                margin: [0, 0, 0, 10]
            };
            doc.styles.tableHeader = {
                fillColor: '#1e3a5f',
                color: '#ffffff',
                bold: true,
                alignment: 'left',
                fontSize: 9
            };

            const tableBlock = (doc.content || []).find(function(block) {
                return block && block.table;
            });

            if (!tableBlock || !tableBlock.table || !Array.isArray(tableBlock.table.body)) {
                return;
            }

            // Force columns to share full available width instead of staying content-sized.
            const headerRow = tableBlock.table.body[0] || [];
            const exportColumnCount = headerRow.length;

            // Auto-choose orientation unless caller explicitly sets one.
            if (!overrides.orientation && exportColumnCount > 0) {
                doc.pageOrientation = exportColumnCount <= AUTO_PORTRAIT_MAX_COLUMNS
                    ? 'portrait'
                    : 'landscape';
            }

            if (headerRow.length > 0) {
                tableBlock.table.widths = Array(headerRow.length).fill('*');
            }

            tableBlock.layout = {
                hLineColor: function() { return '#d1d5db'; },
                vLineColor: function() { return '#d1d5db'; },
                hLineWidth: function() { return 0.6; },
                vLineWidth: function() { return 0.6; },
                paddingLeft: function() { return 5; },
                paddingRight: function() { return 5; },
                paddingTop: function() { return 4; },
                paddingBottom: function() { return 4; }
            };

            const rows = tableBlock.table.body;
            for (let row = 1; row < rows.length; row += 1) {
                const fillColor = row % 2 === 0 ? '#f8fafc' : '#ffffff';
                for (let col = 0; col < rows[row].length; col += 1) {
                    const cell = rows[row][col];
                    if (cell && typeof cell === 'object' && !Array.isArray(cell)) {
                        cell.fillColor = fillColor;
                    }
                }
            }
        }
    };

    return $.extend(true, {}, baseConfig, overrides);
}

window.datatableExportableColumn = datatableExportableColumn;
window.buildPdfButtonConfig = buildPdfButtonConfig;
window.formatDateIst = formatDateIst;
window.formatDateTimeIst = formatDateTimeIst;
window.istDateColumn = istDateColumn;

function loadDatatable(tableId, url, columns, additionalOptions = {}) {
    const tableSelector = `#${tableId}`;
    const table = $(tableSelector);

    if (table.length && !table.parent().hasClass('table-responsive')) {
        table.wrap('<div class="table-responsive"></div>');
    }

    const defaultOptions = {
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            type: 'GET',
            error: function(xhr) {
                if (xhr.status === 401) {
                    toastr.error('Session expired. Please login again.');
                    setTimeout(function() {
                        window.location.href = '/admin/login';
                    }, 2000);
                } else {
                    toastr.error('Failed to load data. Please try again.');
                }
            }
        },
        columns: columns,
        order: [[0, 'desc']],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No data available',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            lengthMenu: 'Show _MENU_ entries',
            search: 'Search:',
            zeroRecords: 'No matching records found',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"f><"col-sm-12 col-md-4 text-md-end"B>>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel',
                className: 'btn btn-success btn-sm dt-export-btn dt-export-excel',
                exportOptions: {
                    columns: datatableExportableColumn
                }
            },
            buildPdfButtonConfig()
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']]
    };

    const options = $.extend(true, {}, defaultOptions, additionalOptions);
    return $(tableSelector).DataTable(options);
}

function showValidationErrors(formId, errors) {
    const selector = getFormSelector(formId);
    const form = $(selector);

    clearValidationErrors(formId);

    let rendered = false;

    $.each(errors, function(field, messages) {
        const normalizedField = normalizeFieldSelector(field);

        let input = form.find(`:input[name="${field}"], :input[name="${normalizedField}"]`).first();

        if (input.length === 0) {
            form.find(':input').each(function() {
                const name = $(this).attr('name') || '';
                const normalizedName = name.replace(/\[/g, '.').replace(/\]/g, '');

                if (name === normalizedField || normalizedName === field) {
                    input = $(this);
                    return false;
                }
            });
        }

        if (input.length === 0) {
            return;
        }

        rendered = true;
        const errorDiv = $('<div class="invalid-feedback d-block"></div>').text(messages[0]);
        const container = getValidationContainer(input);

        input.addClass('is-invalid');
        if (input.hasClass('select2-hidden-accessible')) {
            input.next('.select2-container').addClass('is-invalid');
        }

        container.after(errorDiv);
    });

    if (!rendered && Object.keys(errors).length > 0) {
        const alert = $('<div class="alert alert-danger"><ul class="mb-0"></ul></div>');
        const list = alert.find('ul');

        $.each(errors, function(_, messages) {
            list.append($('<li></li>').text(messages[0]));
        });

        form.prepend(alert);
    }

    return rendered;
}

function clearValidationErrors(formId) {
    const selector = getFormSelector(formId);
    const form = $(selector);

    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
    form.find('.select2-container.is-invalid').removeClass('is-invalid');
}

function ajaxErrorHandler(xhr, defaultMessage = 'An error occurred. Please try again.') {
    if (xhr.status === 422) {
        return;
    }

    if (xhr.status === 401 || xhr.status === 419) {
        toastr.error('Session expired. Please login again.');
        setTimeout(function() {
            window.location.href = '/admin/login?expired=1';
        }, 2000);
    } else if (xhr.status === 403) {
        toastr.error('You do not have permission to perform this action.');
    } else if (xhr.status === 404) {
        toastr.error('Resource not found.');
    } else if (xhr.status === 500) {
        toastr.error('Server error. Please try again later.');
    } else {
        toastr.error(xhr.responseJSON?.message || defaultMessage);
    }
}

function formatCurrency(amount, currency = 'INR') {
    const formatter = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    return formatter.format(amount);
}

function formatDate(date, format = 'DD/MM/YYYY') {
    if (!date) {
        return '-';
    }

    return moment(date).format(format);
}

function debounce(func, wait) {
    let timeout;

    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };

        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function printElement(elementId, title = '') {
    const element = document.getElementById(elementId);
    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
        <html>
            <head>
                <title>${title}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    @media print {
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function enforceResponsiveTables(scope = document) {
    $(scope)
        .find('table.table')
        .each(function() {
            const table = $(this);

            // Skip nested tables and DataTables internals that already manage wrappers.
            if (table.closest('.table-responsive, .dataTables_wrapper').length > 0) {
                return;
            }

            table.wrap('<div class="table-responsive"></div>');
        });
}

function getPartyQuickAddModal() {
    return $('#partyQuickAddModal');
}

function resetPartyQuickAddModal() {
    const modal = getPartyQuickAddModal();

    if (!modal.length) {
        return;
    }

    const form = modal.find('#partyQuickAddForm');
    if (form.length) {
        form[0].reset();
    }

    modal.find('[name="party_target"]').val('');
    modal.find('[name="type"]').val('debtor');
    modal.find('[name="opening_balance_type"]').val('debit');
    modal.find('[name="state_id"]').html('<option value="">Loading states...</option>').prop('disabled', true);
    modal.find('[name="city_id"]').html('<option value="">Select City</option>').prop('disabled', true);
    clearValidationErrors('#partyQuickAddForm');
}

function populateQuickAddSelect(select, items, placeholder, selectedId = null) {
    select.empty().append(`<option value="">${placeholder}</option>`);

    items.forEach(function(item) {
        const option = $('<option></option>')
            .val(item.id)
            .text(item.name);

        if (selectedId && String(selectedId) === String(item.id)) {
            option.prop('selected', true);
        }

        select.append(option);
    });
}

function loadQuickAddStates(countryId, selectedStateId = null) {
    const modal = getPartyQuickAddModal();
    const stateSelect = modal.find('[name="state_id"]');
    const citySelect = modal.find('[name="city_id"]');

    stateSelect.prop('disabled', true).html('<option value="">Loading states...</option>');
    citySelect.prop('disabled', true).html('<option value="">Select City</option>');

    $.getJSON('/api/v1/states')
        .done(function(response) {
            const states = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
            populateQuickAddSelect(stateSelect, states, 'Select State', selectedStateId);
            stateSelect.prop('disabled', false);

            if (selectedStateId) {
                stateSelect.trigger('change');
            }
        })
        .fail(function() {
            stateSelect.html('<option value="">Failed to load states</option>');
        });
}

function loadQuickAddCities(stateId, selectedCityId = null) {
    const modal = getPartyQuickAddModal();
    const citySelect = modal.find('[name="city_id"]');

    citySelect.prop('disabled', true).html('<option value="">Loading cities...</option>');

    if (!stateId) {
        citySelect.html('<option value="">Select City</option>');
        return;
    }

    $.getJSON(`/api/v1/states/${stateId}/cities`)
        .done(function(response) {
            const cities = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
            populateQuickAddSelect(citySelect, cities, 'Select City', selectedCityId);
            citySelect.prop('disabled', false);
        })
        .fail(function() {
            citySelect.html('<option value="">Failed to load cities</option>');
        });
}

function openPartyQuickAddModal(trigger) {
    const button = $(trigger);
    const modal = getPartyQuickAddModal();

    if (!modal.length) {
        return;
    }

    const partyType = button.data('partyQuickAddType') || 'debtor';
    const targetSelector = button.data('partyQuickAddTarget') || '';
    const titleLabel = partyType === 'creditor' ? 'Supplier' : 'Customer';

    modal.find('[name="party_target"]').val(targetSelector);
    modal.find('[name="type"]').val(partyType);
    modal.find('[name="opening_balance_type"]').val(partyType === 'creditor' ? 'credit' : 'debit');
    modal.find('.party-quick-add-title').text(`Quick Add ${titleLabel}`);
    modal.find('.party-quick-add-submit').text(`Create ${titleLabel}`);

    resetPartyQuickAddModal();
    modal.find('[name="type"]').val(partyType);
    modal.find('[name="opening_balance_type"]').val(partyType === 'creditor' ? 'credit' : 'debit');
    modal.find('[name="party_target"]').val(targetSelector);

    loadQuickAddStates();

    const instance = bootstrap.Modal.getOrCreateInstance(modal[0]);
    instance.show();
}

function initPartyQuickAdd() {
    const modal = getPartyQuickAddModal();

    if (!modal.length) {
        return;
    }

    const storeUrl = modal.data('store-url');
    if (!storeUrl) {
        return;
    }

    modal.off('change.partyQuickAdd', '[name="state_id"]');
    modal.on('change.partyQuickAdd', '[name="state_id"]', function() {
        loadQuickAddCities($(this).val());
    });

    modal.off('hidden.bs.modal.partyQuickAdd').on('hidden.bs.modal.partyQuickAdd', function() {
        resetPartyQuickAddModal();
    });

    $(document).off('click.partyQuickAdd', '.quick-add-party-btn');
    $(document).on('click.partyQuickAdd', '.quick-add-party-btn', function() {
        openPartyQuickAddModal(this);
    });

    ajaxFormSubmit('#partyQuickAddForm', storeUrl, 'POST', function(response) {
        const modalInstance = bootstrap.Modal.getInstance(modal[0]);
        const party = response.data || {};
        const targetSelector = modal.find('[name="party_target"]').val();
        const target = targetSelector ? $(targetSelector).first() : $();

        if (target.length) {
            const optionText = party.party_code && party.name ? `${party.party_code} - ${party.name}` : (party.name || 'New Party');
            const optionExists = target.find(`option[value="${party.id}"]`).length > 0;

            if (!optionExists) {
                target.append(new Option(optionText, party.id, true, true));
            }

            target.val(party.id).trigger('change');
        }

        if (modalInstance) {
            modalInstance.hide();
        }
    });
}

$(document).ready(function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    setTimeout(function() {
        $('.alert-auto-hide').fadeOut('slow');
    }, 5000);

    initAjaxForms();
    initPartyQuickAdd();
    enforceResponsiveTables();
});