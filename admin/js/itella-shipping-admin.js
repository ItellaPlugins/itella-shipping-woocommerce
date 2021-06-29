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
        $('.itella-price_by_weight .field-radio input[type="radio"]').on('change', function(){
            toggle_prices($(this).closest('.itella-price_by_weight'));
        });

        $('.itella-price_by_weight .field-table .remove-row').on('click', function(e) {
            e.preventDefault();
            var step = $(this).closest('.row-values').find('.column-values input[type="number"]').attr('step');
            remove_prices_table_row(this, step);
        });

        $('.itella-price_by_weight .field-table .insert-row').on('click', function(e) {
            e.preventDefault();
            add_prices_table_row(this);
        });

        $('.itella-price_by_weight .field-table .column-values input[type="number"]').on('keyup change', function() {
            update_prices_table_values(this);
        });
    });

    function toggle_prices(top_elem) {
        var radios = $(top_elem).find('.field-radio input[type="radio"]');
        var number = $(top_elem).find('.field-number');
        var table_weight = $(top_elem).find('.field-table.table-weight');
        var table_prices = $(top_elem).find('.field-table.table-price');;
        var selected = '';

        for (var i=0; i<radios.length; i++) {
          if ($(radios[i]).is(':checked')) {
            if ($(radios[i]).val() == 'single') {
              $(number).slideDown('slow');
              $(table_weight).slideUp('slow');
              $(table_prices).slideUp('slow');
              selected = 'single';
            }
            if ($(radios[i]).val() == 'weight') {
              $(number).slideUp('slow');
              $(table_weight).slideDown('slow');
              $(table_prices).slideUp('slow');
              selected = 'weight';
            }
            if ($(radios[i]).val() == 'price') {
              $(number).slideUp('slow');
              $(table_weight).slideUp('slow');
              $(table_prices).slideDown('slow');
              selected = 'weight';
            }
          }
        }
        if (!selected) {
          $(number).slideUp('slow');
          $(table_weight).slideUp('slow');
          $(table_prices).slideUp('slow');
        }
    }

    function remove_prices_table_row(rm_btn, step) {
        var row = $(rm_btn).closest('.row-values');
        var prev_row = $(row).prev('.row-values');
        var next_row = $(row).next('.row-values');
        if (next_row.length) {
            var prev_value = 0;
            if (prev_row.length) {
                prev_value = $(prev_row).find('.column-values input[type="number"]').val();
                prev_value = parseFloat(prev_value) + parseFloat(step);
            }
            $(next_row).find('.column-values .from_value').text('' + prev_value.toFixed(itella_countDecimals(step)) + ' -');
        }
        $(row).css('background-color', '#ffd4d4');
        setTimeout(function() {
            var table = $(rm_btn).closest('.row-values').parent();
            $(row).remove();
            var all_value_inputs = $(table).find('.column-values input[type="number"]');
            $(all_value_inputs).prop('readonly', false);
            $(all_value_inputs).last().prop('readonly', true);
        }, 1000);
    }

    function add_prices_table_row(add_btn) {
        var id = $(add_btn).data('id');
        var step = $(add_btn).data('step');
        var type = $(add_btn).data('type');
        var btn_row = $(add_btn).closest('tr');
        var prev_row = $(btn_row).prev('.row-values');
        var prev_value = 0;
        if (prev_row.length) {
            prev_value = $(prev_row).find('.column-values input[type="number"]').val();
            prev_value = parseFloat(prev_value) + parseFloat(step);
        }
        $(btn_row).parent().find('.column-values input[type="number"]').prop('readonly', false);
        var row_params = {
          id: id,
          type: type,
          prev_value: prev_value,
          step: step
        };
        var new_row = build_prices_table_row(row_params);
        $(new_row).insertBefore(btn_row);
    }

    function build_prices_table_row(params) {
        var d = new Date();
        var date = '' + d.getHours() + d.getMinutes() + d.getSeconds();

        var new_row = $('<tr>').attr('valign', 'middle');
        $(new_row).addClass('row-values');
        var new_col_values = $('<td>').addClass('column-values');
        var min_value = params.prev_value;
        if (isNaN(params.prev_value)) {
            min_value = 0;
        }
        var new_col_value_input = $('<input>')
            .attr('type', 'number')
            .attr('value', '')
            .attr('min', min_value)
            .attr('step', params.step)
            .attr('id', params.id + '_' + params.type + '_' + date)
            .attr('name', params.id + '[' + params.type + '][' + date + '][value]');
        $(new_col_value_input).prop('readonly', true);
        $(new_col_value_input).on('keyup change', function() {
            update_prices_table_values(this);
        });
        var decimals = itella_countDecimals(params.step);
        $(new_col_values).append("<span class='from_value'>" + params.prev_value.toFixed(decimals) + " -</span> ");
        $(new_col_values).append(new_col_value_input);

        var new_col_price = $('<td>').addClass('column-price');
        var new_col_price_input = $('<input>')
            .attr('type', 'number')
            .attr('value', '')
            .attr('min', 0)
            .attr('step', 0.01)
            .attr('id', params.id + '_' + params.type + '_price_' + date)
            .attr('name', params.id + '[' + params.type + '][' + date + '][price]');
        $(new_col_price).append(new_col_price_input);

        var new_col_actions = $('<td>').addClass('column-actions');
        var new_col_actions_remove = $('<button>').addClass('remove-row').text('X');
        $(new_col_actions_remove).on('click',function(e) {
            e.preventDefault();
            remove_prices_table_row(this, params.step);
        });
        $(new_col_actions).append(new_col_actions_remove);

        $(new_row).append(new_col_values);
        $(new_row).append(new_col_price);
        $(new_row).append(new_col_actions);

        return new_row;
    }

    function update_prices_table_values(input) {
        var row = $(input).closest('.row-values');
        var row_value = $(input).val();
        var prev_row = $(row).prev('.row-values');
        var next_row = $(row).next('.row-values');
        var step = $(input).attr('step');
        var decimals = itella_countDecimals(step);
        if (prev_row.length) {
            var max_value = parseFloat(row_value) - parseFloat(step);
            $(prev_row).find('.column-values input[type="number"]').attr('max',max_value.toFixed(decimals));
        }
        if (next_row.length) {
            var next_row_span = $(next_row).find('.column-values .from_value');
            var next_value = parseFloat(row_value) + parseFloat(step);
            $(next_row).find('.column-values input[type="number"]').attr('min',next_value.toFixed(decimals));
            $(next_row_span).text('' + next_value.toFixed(decimals) + ' -');
        }
    }

    function itella_countDecimals(value) {
      if ((value % 1) != 0) 
        return value.toString().split(".")[1].length;  
      return 0;
    }
})(jQuery);