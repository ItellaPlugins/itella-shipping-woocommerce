jQuery('document').ready(function($) {
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
        $('#filter_id, #filter_customer, #filter_barcode, #datetimepicker1, #datetimepicker2').val('');
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
        $('.manifest-item:checked').each(function () {
            ids += $(this).val() + ";";
            let id = $(this).val();
            $('#call-courier-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
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

    $('#submit_manifest_items').on('click', function() {
        var ids = "";
        $('#manifest-print-form .post_id').remove();
        $('.manifest-item:checked').each(function() {
            ids += $(this).val() + ";";
            var id = $(this).val();
            $('#manifest-print-form').append('<input type="hidden" class = "post_id" name="post[]" value = "' + id + '" />');
        });
        $('#item_ids').val(ids);
        if (ids == "") {
            alert(translations.select_orders);
        } else {
            $('#manifest-print-form').submit();
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
});