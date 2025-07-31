window.itellaBulkActions = {
    get_selected_action: function() {
        const action1Select = document.querySelector('select[name="action"]');
        const action2Select = document.querySelector('select[name="action2"]');

        const action1Value = action1Select ? action1Select.value : null;
        const action2Value = action2Select ? action2Select.value : null;

        return (action1Value !== '-1') ? action1Value : action2Value;
    },

    get_selected_orders: function() {
        return Array.from(document.querySelectorAll('input[name="id[]"]:checked, input[name="post[]"]:checked')).map(input => input.value);
    },

    ajax: function({ url, method = 'POST', data = null, headers = {}, success, error }) {
        const fetchOptions = {
            method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                ...headers
            }
        };

        if (data && method.toUpperCase() !== 'GET') {
            const params = new URLSearchParams();
            for (const key in data) {
                const value = data[key];
                if (Array.isArray(value)) {
                    value.forEach(v => params.append(`${key}[]`, v));
                } else {
                    params.append(key, value);
                }
            }
            fetchOptions.body = params.toString();
        }

        if (data && method.toUpperCase() === 'GET') {
            const params = new URLSearchParams(data).toString();
            url += (url.includes('?') ? '&' : '?') + params;
        }

        fetch(url, fetchOptions)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (typeof success === 'function') success(data);
            })
            .catch(err => {
                if (typeof error === 'function') error(err);
            });
    },

    action_register_shipments: function() {
        document.querySelector('form#wc-orders-filter, form#posts-filter')?.addEventListener('submit', function(e) {
            const clickedElement = document.activeElement;
            if (clickedElement?.name !== 'bulk_action') {
                return;
            }

            const selectedAction = itellaBulkActions.get_selected_action();

            if ( selectedAction === 'itella_register_shipments' ) {
                const selectedOrders = itellaBulkActions.get_selected_orders();

                if ( selectedOrders.length === 0 ) {
                    return;
                }

                e.preventDefault();
                
                itellaPopup.show(itellaTranslations.registering_shipments, 'warning', false, 0, true);
                itellaBulkActions.ajax_register_shipments(selectedOrders);
            }
        });
    },

    ajax_register_shipments: function(ids) {
        itellaBulkActions.ajax({
            url: itellaParams.ajax_url,
            method: 'POST',
            data: {
                action: 'bulk_register_shipments',
                ids: ids,
                nonce: itellaParams.nonce_register
            },
            success: function(response) {
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

                if (response.status === "notice" && response.hasOwnProperty("values")) {
                    itellaPopup.show(buildMessage(output, response.values), 'warning');
                }

                if (response.status === "notice" && response.hasOwnProperty("order_ids") && response.order_ids) {
                    itellaBulkActions.ajax_check_crons();
                }

                if (response.status === "error") {
                    const errorMsg = response.msg || itellaTranslations.error_unknown;
                    itellaPopup.show(errorMsg, 'error');
                }
            },
            error: function(err) {
                const errorMsg = err.message || itellaTranslations.error_unknown;
                itellaPopup.show(errorMsg, 'error');
            }
        });
    },

    ajax_check_crons: function(args) {
        itellaBulkActions.ajax({
            url: itellaParams.ajax_url,
            method: 'POST',
            data: {
                action: 'itella_check_ongoing_registrations',
                nonce: itellaParams.nonce_register
            },
            success: function(response) {
                console.log('Smartposti shipment registration check', response);
                if ( response.completed ) {
                    itellaPopup.show(itellaTranslations.register_completed, 'success');
                } else {
                    if ( response.actions_left ) {
                        let msg = itellaTranslations.registering_shipments + '<br/>';
                        msg += itellaTranslations.left_actions.replace('%d', response.actions_left);
                        itellaPopup.show(msg, 'warning', false, 0, true);
                    }

                    setTimeout(() => itellaBulkActions.ajax_check_crons(args), 3000);
                }
            },
            error: function(err) {
                const errorMsg = err.message || itellaTranslations.error_unknown;
                itellaPopup.show(errorMsg, 'error');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.itellaBulkActions.action_register_shipments();
});
