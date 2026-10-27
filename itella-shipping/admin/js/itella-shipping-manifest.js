jQuery('document').ready(function($) {
    // Modal date picker
    $('#modaldatetimepicker').datetimepicker({
        minDate: new Date(),
        defaultDate: new Date(),
        pickTime: false,
        useCurrent: false,
        closeOnDateSelect:true
    });
    // "From" date picker
    $('#datetimepicker1').datetimepicker({
        pickTime: false,
        useCurrent: false
    });
    // "To" date picker
    $('#datetimepicker2').datetimepicker({
        pickTime: false,
        useCurrent: false
    });

    // Set limits depending on date picker selections
    $("#datetimepicker1").on("dp.change", function(e) {
        $('#datetimepicker2').data("DateTimePicker").setMinDate(e.date);
    });
    $("#datetimepicker2").on("dp.change", function(e) {
        $('#datetimepicker1').data("DateTimePicker").setMaxDate(e.date);
    });

    // Pass on filters to pagination links
    $('.tablenav-pages').on('click', 'a', function(e) {
        e.preventDefault();
        var form = document.getElementById('filter-form');
        form.action = e.target.href;
        form.submit();
    });

    // Filter cleanup and page reload
    $('#clear_filter_btn').on('click', function(e) {
        e.preventDefault();
        $('#filter_id, #filter_customer, #filter_tracking_code, #datetimepicker1, #datetimepicker2').val('');
        $('#filter_status').val('-1');
        document.getElementById('filter-form').submit();
    });

    $('#itella-courier-modal').on('click', function(e) {
        if (e.target === this) {
            $('#itella-courier-modal').removeClass('open');
        }
    });

    $('.itella-call-courier-btn').on('click', function(e) {
        e.preventDefault();
        // Remember manifest group, if button belongs to one
        $('#itella-call-courier-form').data('manifest-date', $(this).attr('data-manifest-date') || '');
        $('#itella-courier-modal').addClass('open');
    });

    // Handles both submit button click and Enter key
    $('#itella-call-courier-form').on('submit', function(e) {
        var form = $(this);
        var submit_btn = $('#itella-call-courier-submit');
        if (submit_btn.prop('disabled')) {
            e.preventDefault();
            return;
        }
        form.find('.post_id').remove();
        itellaGetSelectedOrderIds(form.data('manifest-date')).forEach(function(id) {
            form.append('<input type="hidden" class="post_id" name="post[]" value="' + id + '" />');
        });
        if (form.find('.post_id').length === 0) {
            e.preventDefault();
            alert(translations.select_orders);
            return;
        }
        submit_btn.prop('disabled', true);
        $('#itella-call-courier-cancel').prop('disabled', true);
    });

    $('#itella-call-courier-cancel').on('click', function(e) {
        e.preventDefault();
        $('#itella-courier-modal').removeClass('open');
    });

    // Re-enable buttons when page is restored from browser back/forward cache
    $(window).on('pageshow', function(e) {
        if (e.originalEvent.persisted) {
            $('#itella-call-courier-submit, #itella-call-courier-cancel').prop('disabled', false);
        }
    });



    function itellaDisableAllBulkButtons(disable) {
        const buttons_selector = [
            '#submit_shipments_register',
            '#submit_manifest_labels',
            '#submit_manifest_items'
        ];

        buttons_selector.forEach(selector => {
            const btn = document.querySelector(selector);
            if (btn) {
                btn.disabled = disable;
            }
        });
    }



    $('#submit_shipments_register').on('click', function(e) {
        var ids = [];
        $('#register-print-form .post_id').remove();
        $('.manifest-item:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            itellaPopup.show(translations.select_orders, 'error');
            return;
        }

        itellaDisableAllBulkButtons(true);
        var button = $(this);
        button.find('.spinner-holder').addClass('active');

        itellaShipmentRegistration.register({
            ajax_url: manifest_ajax.ajax_url,
            nonce: $('#itella_shipments_nonce').val(),
            ids: ids,
            translations: translations,
            callbacks: {
                onError: function() {
                    button.find('.spinner-holder').removeClass('active');
                    itellaDisableAllBulkButtons(false);
                },
                onComplete: function() {
                    itellaDisableAllBulkButtons(false);
                    button.find('.spinner-holder').removeClass('active');
                    setTimeout(function() { location.reload(); }, 2000);
                }
            }
        });
    });



    $('.itella-register-shipment').on('click', function(e) {
        $(this).prop('disabled', true);
        $(this).find('.spinner-holder').addClass('active');
        var id = $(this).data("id");
        var button = $(this);
        var nonce = $('#itella_shipments_nonce').val();
        $.ajax({
            type: "post",
            dataType: "json",
            url: manifest_ajax.ajax_url,
            data: {
                action : "single_register_shipment",
                id : id,
                nonce : nonce
            },
            success: function(response){
                if (response.status == 'error') {
                    alert(response.msg);
                }
                $(button).find('.spinner-holder').removeClass('active');
                $(button).prop('disabled', false);
                if (response.status == 'success') {
                    alert(response.msg);
                    location.reload();
                }
            },
            error: function(xhr, status, error){
                var errorMessage = xhr.status + ': ' + xhr.statusText;
                alert('Error - ' + errorMessage);
                $(button).find('.spinner-holder').removeClass('active');
                $(button).prop('disabled', false);
            }
        });
    });

    $('#itella-manifest-cb').on('change', function() {
        var checked = $(this).is(':checked');
        $('#manifest-print-form .print_all').remove();
        if(checked) {
            if(!confirm(translations.switch_confirm)) {         
                $(this).removeAttr('checked');
            } else {
                var tab_name = $(this).data("tab");
                $('#manifest-print-form').append('<input type="hidden" class="print_all" name="for_all" value="' + tab_name + '" />');
            }
        }
    });

    $('#submit_manifest_items').on('click', function() {
        var ids = "";
        $('#manifest-print-form .post_id').remove();
        $('.manifest-item:checked').each(function() {
            ids += $(this).val() + ";";
            var id = $(this).val();
            $('#manifest-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
        $('#item_ids').val(ids);
        if (ids == "" && !$('#itella-manifest-cb').is(':checked')) {
            alert(translations.select_orders);
        } else {
            $('#manifest-print-form').submit();
        }
    });

    $('.submit_manifest_items').on('click', function() {
        var form = $('.manifest-print-form');
        var ids = itellaGetSelectedOrderIds($(this).attr('data-manifest-date'));
        form.find('.post_id').remove();
        ids.forEach(function(id) {
            form.append('<input type="hidden" class="post_id" name="post[]" value="' + id + '" />');
        });
        if (ids.length === 0) {
            alert(translations.select_orders);
        } else {
            form.submit();
        }
    });

    $('#submit_manifest_labels').on('click', function() {
        var ids = "";
        $('#labels-print-form .post_id').remove();
        $('.manifest-item:checked').each(function() {
            ids += $(this).val() + ";";
            var id = $(this).val();
            $('#labels-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
        if (ids == "") {
            alert(translations.select_orders);
        } else {
            $('#labels-print-form').submit();
        }
    });

    $('.check-all').on('click', function() {
        var checked = $(this).prop('checked');
        $(this).parents('table').find('.manifest-item').each(function() {
            $(this).prop('checked', checked);
        });
    });

    $('#itella-show-pp').on('change', function() {
        window.location = add_param_to_url('show_pp', $(this).val(), ["show_pp", "paged"]);
    });
});

/**
 * Returns checked order IDs. When nothing is checked and manifest date is given,
 * returns all orders of that manifest group.
 */
function itellaGetSelectedOrderIds( manifestDate ) {
    var items = jQuery('.manifest-item:checked');
    if (items.length === 0 && manifestDate) {
        items = jQuery('.manifest-item').filter(function() {
            return jQuery(this).attr('data-manifest-date') === manifestDate;
        });
    }
    return items.map(function() {
        return jQuery(this).val();
    }).get();
}

function add_param_to_url( paramName, paramValue, removeParams = [] ) {
    var url = window.location.href;
    if (removeParams.length > 0) {
        for (var i = 0; i < removeParams.length; ++i) {
            var pattern = new RegExp("[?&]" + removeParams[i] + "=([^&]+)");
            url = url.replace(pattern, "");
        }
    }
    if (url.indexOf("?") < 0) {
        url += "?" + paramName + "=" + paramValue;
    } else {
        url += "&" + paramName + "=" + paramValue;
    }
    return url;
}

