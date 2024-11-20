'use strict';

(() => {
    window.addEventListener('load', (event) => {

        // toggle extra fee fields
        const pickupPoint = document.querySelector('.method-cb-pickup_point');
        const courier = document.querySelector('.method-cb-courier');
        const pickupPointFields = document.querySelectorAll('.field-toggle-pickup_point');
        const courierFields = document.querySelectorAll('.field-toggle-courier');
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

(() => {
    // Methods
    window.addEventListener("load", (event) => {
        // Global
        itellaSettingsMethods.methods.forEach( method => {
            itellaSettingsMethods.toggle_method(method);
            itellaSettingsMethods.get_global_switcher(method).addEventListener("change", function () {
                itellaSettingsMethods.toggle_method(method);
            });
        });

        // Country method
        document.querySelectorAll(".itella-method .row-price_type select").forEach( select_field => {
            var method_box = select_field.closest(".itella-method");
            itellaSettingsMethods.toggle_prices(method_box, select_field.value);
            select_field.addEventListener("change", function () {
                itellaSettingsMethods.toggle_prices(method_box, select_field.value);
            });
        });
    });

    // Prices
    document.querySelectorAll(".itella-method .values_table .insert_row").forEach( insert_btn => {
        insert_btn.addEventListener("click", function( event ) {
            event.preventDefault();
            itellaSettingsPrices.add_row(this);
            itellaSettingsPrices.arrange_values(this.closest(".values_table"));
        });
    });
    document.querySelectorAll(".itella-method .values_table").forEach( table => {
        table.addEventListener("click", function( event ) {
            if ( event.target && event.target.classList.contains("remove_row") ) {
                event.preventDefault();
                itellaSettingsPrices.remove_row(event.target);
                itellaSettingsPrices.arrange_values(event.target.closest(".values_table"));
            }
        });
        table.addEventListener("change", function( event ) {
            if ( event.target && event.target.tagName.toUpperCase() == 'INPUT' ) {
                itellaSettingsPrices.arrange_values(event.target.closest(".values_table"));
            }
        });
    });
})();

window.itellaHelpers = {
    count_decimals: function( value ) {
        if ((value % 1) != 0) 
            return value.toString().split(".")[1].length;  
        return 0;
    }
};

window.itellaSettingsMethods = {
    methods: ["pickup_point", "courier"],
    prices: ["single", "weight", "amount"],

    get_global_switcher: function( method ) {
        return document.querySelector(".method-cb-" + method);
    },

    toggle_method: function( method ) {
        var checkbox = itellaSettingsMethods.get_global_switcher(method);
        var blocks = document.querySelectorAll(".itella-methods .itella-method-" + method);

        blocks.forEach( block => {
            if ( checkbox.checked ) {
                block.classList.remove("disabled");
            } else {
                block.classList.add("disabled");
            }
        });
    },

    toggle_prices: function( block, selected_value ) {
        itellaSettingsMethods.prices.forEach( price_field => {
            if ( price_field == selected_value ) {
                block.querySelector(".row-price-" + price_field).style.display = "";
            } else {
                block.querySelector(".row-price-" + price_field).style.display = "none";
            }
        });
        if ( itellaSettingsMethods.prices.includes(selected_value) ) {
            block.classList.remove("off");
        } else {
            block.classList.add("off");
        }
    }
};

window.itellaSettingsPrices = {
    add_row: function( btn_insert ) {
        var table = btn_insert.closest(".values_table").querySelector("table");
        var row_default = table.querySelector(".table_row-default");
        var all_rows = table.querySelectorAll(".table_row-field");

        table.querySelector(".table_row-default").style.display = "none";

        var field_param_min = "";
        if ( btn_insert.dataset.type == "range" ) {
            if ( all_rows.length > 1 ) {
                var row_last = all_rows[all_rows.length - 1];
                field_param_min = row_last.querySelector(".table_col_1 input").value;
            }
        }
        
        var row_cloned = row_default.cloneNode(true);
        row_cloned = itellaSettingsPrices.clear_cloned_row(row_cloned);
        row_cloned = itellaSettingsPrices.fill_cloned_row(row_cloned, {
            row_no: Date.now(),
            type: btn_insert.dataset.type,
            field: {
                id: btn_insert.dataset.id,
                name: btn_insert.dataset.name,
                step: btn_insert.dataset.step,
                min: field_param_min,
            }
        });
        itellaSettingsPrices.add_row_actions(row_cloned);

        table.getElementsByTagName('tbody')[0].appendChild(row_cloned);
    },

    clear_cloned_row: function( row ) {
        row.style.display = "";
        row.classList.remove("table_row-default");
        row.classList.add("table_row-field");

        return row;
    },

    fill_cloned_row: function( row, params ) {
        var value_field = null;
        var price_field = row.querySelector(".table_col_2 input");

        row.classList.add("table_row_" + params.row_no);

        if ( params.type == "range" ) {
            value_field = row.querySelector(".table_col_1 input");
        }
        if ( params.type == "select" ) {
            value_field = row.querySelector(".table_col_1 select");
            for ( var i = 0; i < value_field.length; i++ ) {
                if ( value_field.options[i].innerHTML == '-' ) {
                    value_field.remove(i);
                }
            }
        }

        if ( ! value_field ) {
            console.error("Itella error: Failed to get value field.");
            return row;
        }

        value_field.id = params.field.id + "_" + params.row_no + "_value";
        value_field.setAttribute("name", params.field.name + "[" + params.row_no + "][value]");
        value_field.disabled = false;

        price_field.id = params.field.id + "_" + params.row_no + "_price";
        price_field.setAttribute("name", params.field.name + "[" + params.row_no + "][price]");
        price_field.disabled = false;

        return row;
    },

    arrange_values: function( table = false ) {
        var tables = null;
        if ( ! table ) {
            tables = document.querySelectorAll(".itella-methods .values_table");
        } else {
            tables = [table];
        }

        for ( var i = 0; i < tables.length; i++ ) {
            var table_rows = tables[i].querySelectorAll(".table_row-field");
            for ( var j = 0; j < table_rows.length; j++ ) {
                var value_field = table_rows[j].querySelector(".table_col_1 input");
                if ( value_field ) {
                    var range_col = table_rows[j].querySelector(".table_col_0")
                    var last_value = 0;
                    var next_value = "";
                    var step = value_field.getAttribute("step");
                    
                    if ( j > 0 ) {
                        last_value = table_rows[j - 1].querySelector(".table_col_1 input").value;
                    }
                    if ( j + 1 < table_rows.length ) {
                        next_value = table_rows[j + 1].querySelector(".table_col_1 input").value;
                    }

                    if ( j == 0 ) {
                        value_field.setAttribute("min", 0);
                        table_rows[j].querySelector(".table_col_0").innerHTML = "0.000 -";
                    } else {
                        var min_value = parseFloat(last_value) + parseFloat(step);
                        if (isNaN(min_value)) {
                            min_value = 0;
                        }
                        min_value = min_value.toFixed(itellaHelpers.count_decimals(step));
                        value_field.setAttribute("min", min_value);
                        table_rows[j].querySelector(".table_col_0").innerHTML = "" + min_value + " -";
                    }

                    if ( next_value !== "" ) {
                        var max_value = parseFloat(next_value) - parseFloat(step);
                        value_field.setAttribute("max", max_value.toFixed(itellaHelpers.count_decimals(step)));
                    }
                }
            }
        }
    },

    add_row_actions: function( row, params = [] ) {
        var actions_col = row.querySelector(".table_col_3");

        var btn_remove_row = document.createElement("button");
        btn_remove_row.innerHTML = "X";
        btn_remove_row.classList.add("remove_row");
        actions_col.appendChild(btn_remove_row);
    },

    remove_row: function( btn_remove ) {
        var table = btn_remove.closest(".values_table").querySelector("table");
        var row = btn_remove.closest(".table_row-field");
        row.remove();

        var all_rows = table.querySelectorAll(".table_row-field");
        if ( ! all_rows.length ) {
            table.querySelector(".table_row-default").style.display = "";
        }
    }
};

(function($){
    $(document).ready(function() {


        /*$('.itella-price_by_weight .field-radio input[type="radio"]').on('change', function(){
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
        });*/
    });

    /****/
    /*function toggle_prices(top_elem) {
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
    }*/

    /*function remove_prices_table_row(rm_btn, step) {
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
    }*/

    /*function add_prices_table_row(add_btn) {
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
    }*/

    /*function build_prices_table_row(params) {
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
    }*/

    /*function update_prices_table_values(input) {
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
    }*/

    function itella_countDecimals(value) {
      if ((value % 1) != 0) 
        return value.toString().split(".")[1].length;  
      return 0;
    }
})(jQuery);