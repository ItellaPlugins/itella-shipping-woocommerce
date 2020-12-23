'use strict';

(() => {
    window.addEventListener('load', (event) => {

        // toggle extra fee fields
        const pickupPoint = document.querySelector('.pickup-point-method');
        const courier = document.querySelector('.courier-method');
        const pickupPointFields = document.querySelectorAll('.pickup-point');
        const courierFields = document.querySelectorAll('.courier');
        const allfields = document.querySelectorAll('.itella-settings input');
        // const feeTaxField = document.querySelector('.method-fee-tax').parentElement.parentElement.parentElement.parentElement;

        if (!pickupPoint.checked) {
            pickupPointFields.forEach(field => {
                if (pickupPoint.checked) {
                    field.closest("tr").classList.remove('d-none');
                } else {
                    field.closest("tr").classList.add('d-none');
                }
            })
        }
        if (!courier.checked) {
            courierFields.forEach(field => {
                if (courier.checked) {
                    field.closest("tr").classList.remove('d-none');
                } else {
                    field.closest("tr").classList.add('d-none');
                }
            })
        }

        pickupPoint.addEventListener('change', function () {
            pickupPointFields.forEach(field => {
                if (pickupPoint.checked) {
                    field.closest("tr").classList.remove('d-none');
                } else {
                    field.closest("tr").classList.add('d-none');
                }
            });
			// if (feeTaxField.classList.contains('d-none')) {
			// 	feeTaxField.classList.toggle('d-none');
			// }
        });

        courier.addEventListener('change', function () {
            courierFields.forEach(field => {
                if (courier.checked) {
                    field.closest("tr").classList.remove('d-none');
                } else {
                    field.closest("tr").classList.add('d-none');
                }
            });
			// if (feeTaxField.classList.contains('d-none')) {
			// 	feeTaxField.classList.toggle('d-none');
			// }
        });

        allfields.forEach(field => {
            field.addEventListener('invalid', function () {
                this.parentElement.parentElement.parentElement.classList.add("invalid-value");
                var first_invalid = document.querySelector('.itella-settings .invalid-value input');
                first_invalid.scrollIntoView({
                    behavior: 'auto',
                    block: 'center',
                    inline: 'center'
                });
            });
            field.addEventListener('change', function () {
                this.parentElement.parentElement.parentElement.classList.remove("invalid-value");
            });
        });
    });
})();

(function($){
    $(document).ready(function() {    
        $('.itella-price_by_weight .field-cb input[type="checkbox"]').on('change', function(){
            toggle_prices($(this).closest('.itella-price_by_weight'));
        });

        $('.itella-price_by_weight .field-table .remove-row').on('click', function(e) {
            e.preventDefault();
            remove_prices_table_row(this);
        });

        $('.itella-price_by_weight .field-table .insert-row').on('click', function(e) {
            e.preventDefault();
            add_prices_table_row(this);
        });

        $('.itella-price_by_weight .field-table .column-weight input[type="number"]').on('keyup change', function() {
            update_prices_table_weight(this);
        });
    });

    function toggle_prices(top_elem) {
        var cb = $(top_elem).find('.field-cb input[type="checkbox"]');
        var number = $(top_elem).find('.field-number');
        var table = $(top_elem).find('.field-table');
        if ($(cb).is(':checked')) {
            $(number).slideUp('slow');
            $(table).slideDown('slow');
        } else {
            $(number).slideDown('slow');
            $(table).slideUp('slow');
        }
    }

    function remove_prices_table_row(rm_btn) {
        var row = $(rm_btn).closest('.row-values');
        var prev_row = $(row).prev('.row-values');
        var next_row = $(row).next('.row-values');
        if (next_row.length) {
            var prev_value = 0;
            if (prev_row.length) {
                prev_value = $(prev_row).find('.column-weight input[type="number"]').val();
                prev_value = parseFloat(prev_value) + 0.001;
            }
            $(next_row).find('.column-weight .from_value').text('' + prev_value.toFixed(3) + ' -');
        }
        $(row).css('background-color', '#ffd4d4');
        setTimeout(function() {
            var table = $(rm_btn).closest('.row-values').parent();
            $(row).remove();
            var all_weight_inputs = $(table).find('.column-weight input[type="number"]');
            $(all_weight_inputs).prop('readonly', false);
            $(all_weight_inputs).last().prop('readonly', true);
        }, 1000);
    }

    function add_prices_table_row(add_btn) {
        var id = $(add_btn).data('id');
        var btn_row = $(add_btn).closest('tr');
        var prev_row = $(btn_row).prev('.row-values');
        var prev_value = 0;
        if (prev_row.length) {
            prev_value = $(prev_row).find('.column-weight input[type="number"]').val();
            prev_value = parseFloat(prev_value) + 0.001;
        }
        $(btn_row).parent().find('.column-weight input[type="number"]').prop('readonly', false);
        var new_row = build_prices_table_row(id,prev_value);
        $(new_row).insertBefore(btn_row);
    }

    function build_prices_table_row(id,prev_value) {
        var d = new Date();
        var date = '' + d.getHours() + d.getMinutes() + d.getSeconds();

        var new_row = $('<tr>').attr('valign', 'middle');
        $(new_row).addClass('row-values');
        var new_col_weight = $('<td>').addClass('column-weight');
        var min_value = prev_value;
        if (isNaN(prev_value)) {
            min_value = 0;
        }
        var new_col_weight_input = $('<input>')
            .attr('type', 'number')
            .attr('value', '')
            .attr('min', min_value)
            .attr('step', 0.001)
            .attr('id', id + '_weight_' + date)
            .attr('name', id + '[weight][' + date + ']');
        $(new_col_weight_input).prop('readonly', true);
        $(new_col_weight_input).on('keyup change', function() {
            update_prices_table_weight(this);
        });
        $(new_col_weight).append("<span class='from_value'>" + prev_value.toFixed(3) + " -</span> ");
        $(new_col_weight).append(new_col_weight_input);

        var new_col_price = $('<td>').addClass('column-price');
        var new_col_price_input = $('<input>')
            .attr('type', 'number')
            .attr('value', '')
            .attr('min', 0)
            .attr('step', 0.01)
            .attr('id', id + '_price_' + date)
            .attr('name', id + '[price][' + date + ']');
        $(new_col_price).append(new_col_price_input);

        var new_col_actions = $('<td>').addClass('column-actions');
        var new_col_actions_remove = $('<button>').addClass('remove-row').text('X');
        $(new_col_actions_remove).on('click',function(e) {
            e.preventDefault();
            remove_prices_table_row(this);
        });
        $(new_col_actions).append(new_col_actions_remove);

        $(new_row).append(new_col_weight);
        $(new_row).append(new_col_price);
        $(new_row).append(new_col_actions);

        return new_row;
    }

    function update_prices_table_weight(input) {
        var row = $(input).closest('.row-values');
        var row_value = $(input).val();
        var prev_row = $(row).prev('.row-values');
        var next_row = $(row).next('.row-values');
        if (prev_row.length) {
            var max_value = parseFloat(row_value) - 0.001;
            $(prev_row).find('.column-weight input[type="number"]').attr('max',max_value.toFixed(3));
        }
        if (next_row.length) {
            var next_row_span = $(next_row).find('.column-weight .from_value');
            var next_value = parseFloat(row_value) + 0.001;
            $(next_row).find('.column-weight input[type="number"]').attr('min',next_value.toFixed(3));
            $(next_row_span).text('' + next_value.toFixed(3) + ' -');
        }
    }
})(jQuery);