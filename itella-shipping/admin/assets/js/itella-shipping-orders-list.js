window.itellaBulkActions = {
    get_selected_action: function() {
        var action1Select = document.querySelector('select[name="action"]');
        var action2Select = document.querySelector('select[name="action2"]');

        var action1Value = action1Select ? action1Select.value : null;
        var action2Value = action2Select ? action2Select.value : null;

        return (action1Value !== '-1') ? action1Value : action2Value;
    },

    get_selected_orders: function() {
        return Array.from(document.querySelectorAll('input[name="id[]"]:checked, input[name="post[]"]:checked')).map(function(input) {
            return input.value;
        });
    },

    action_register_shipments: function() {
        var form = document.querySelector('form#wc-orders-filter, form#posts-filter');
        if ( ! form ) return;

        form.addEventListener('submit', function(e) {
            var clickedElement = document.activeElement;
            if ( ! clickedElement || clickedElement.name !== 'bulk_action') {
                return;
            }

            var selectedAction = itellaBulkActions.get_selected_action();

            if ( selectedAction === 'itella_register_shipments' ) {
                var selectedOrders = itellaBulkActions.get_selected_orders();

                if ( selectedOrders.length === 0 ) {
                    return;
                }

                e.preventDefault();

                itellaShipmentRegistration.register({
                    ajax_url: itellaParams.ajax_url,
                    nonce: itellaParams.nonce_register,
                    ids: selectedOrders,
                    translations: itellaTranslations
                });
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.itellaBulkActions.action_register_shipments();
});
