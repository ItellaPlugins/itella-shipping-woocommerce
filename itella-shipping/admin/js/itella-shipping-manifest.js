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

    $('#itella-call-btn').on('click', function(e) {
        e.preventDefault();
        $('#itella-courier-modal').addClass('open');
    });

    $('.modal-footer>#itella-call-btn').on('click', function(e) {
        e.preventDefault();
        let ids = "";
        $('#call-courier-form .post_id').remove();
        $('#call-courier-form').find('input[type="hidden"].copied-hidden').remove();
        $('.manifest-item:checked').each(function () {
            ids += $(this).val() + ";";
            let id = $(this).val();
            $('#call-courier-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
        itellaCopyInputsToForm('#itella-courier-modal', '#call-courier-form');
        $('#item_ids').val(ids);
        if (ids == "") {
            alert(translations.select_orders);
        } else {
            $('#call-courier-form').submit();
        }
    });

    $('#itella-call-cancel-btn').on('click', function(e) {
        e.preventDefault();
        $('#itella-courier-modal').removeClass('open');
    });

    $('#submit_shipments_register').on('click', function(e) {
        var ids = [];
        $('#register-print-form .post_id').remove();
        $('.manifest-item:checked').each(function() {
            ids.push($(this).val());
        });
        if (ids.length === 0) {
            alert(translations.select_orders);
        } else {
            var nonce = $('#itella_shipments_nonce').val();
            $(this).prop('disabled', true);
            $(this).find('.spinner-holder').addClass('active');
            var button = $(this);
            $.ajax({
                type: "post",
                dataType: "json",
                url: manifest_ajax.ajax_url,
                data: {
                    action : "bulk_register_shipments",
                    ids : ids,
                    nonce : nonce
                },
                success: function(response){
                    const showAlertAndReload = (message) => {
                        alert(message);
                        location.reload();
                    };

                    const enableButton = () => {
                        $(button).find('.spinner-holder').removeClass('active');
                        $(button).prop('disabled', false);
                    };

                    const buildMessage = (baseMsg, values) => {
                        let output = baseMsg;
                        if (Array.isArray(values)) {
                            output += ':\n';
                            values.forEach(entry => {
                                output += `#${entry.id}: ${entry.msg}\n`;
                            });
                        }
                        return output;
                    };

                    let output = response.msg;

                    if (response.status === "notice" && response.hasOwnProperty("values")) {
                        output = buildMessage(output, response.values);
                    }

                    if (response.hasOwnProperty("confirm_url")) {
                        const confirm_result = confirm(output);
                        if (confirm_result) {
                            window.location.href = response.confirm_url;
                        } else {
                            location.reload();
                        }
                    } else {
                        showAlertAndReload(output);
                    }

                    enableButton();
                },
                error: function(xhr, status, error){
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    alert('Error - ' + errorMessage);
                    $(button).find('.spinner-holder').removeClass('active');
                    $(button).prop('disabled', false);
                }
            });
        }
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
        var ids = "";
        $('.manifest-print-form .post_id').remove();
        $('.manifest-item:checked').each(function() {
            ids += $(this).val() + ";";
            var id = $(this).val();
            $('.manifest-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
        $('#item_ids').val(ids);
        if (ids == "" && !$('#itella-manifest-cb').is(':checked')) {
            alert(translations.select_orders);
        } else {
            $('.manifest-print-form').submit();
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

function itellaCopyInputsToForm( sourceSelector, targetFormSelector ) {
  const source = document.querySelector(sourceSelector);
  const targetForm = document.querySelector(targetFormSelector);

  targetForm.querySelectorAll('input.copied-hidden').forEach(el => el.remove());

  const fields = source.querySelectorAll('input, select, textarea');

  fields.forEach(field => {
    const name = field.name;
    if (!name) return;

    let value;

    if ((field.type === 'checkbox' || field.type === 'radio')) {
      if (field.checked) {
        value = field.value;
      } else {
        return;
      }
    } else {
      value = field.value;
    }

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = name;
    hiddenInput.value = value;
    hiddenInput.classList.add('copied-hidden');

    targetForm.appendChild(hiddenInput);
  });
}

