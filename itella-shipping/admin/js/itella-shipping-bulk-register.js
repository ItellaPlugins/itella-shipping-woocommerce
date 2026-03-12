window.itellaShipmentRegistration = (function() {
    'use strict';

    function buildMessage(baseMsg, values) {
        var output = baseMsg || '';
        if (Array.isArray(values)) {
            output += ':\n';
            for (var i = 0; i < values.length; i++) {
                output += '#' + values[i].id + ': ' + values[i].msg + '\n';
            }
        }
        return output;
    }

    function ajax(params) {
        var fetchOptions = {
            method: params.method || 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };

        if (params.data) {
            var urlParams = new URLSearchParams();
            for (var key in params.data) {
                if ( ! params.data.hasOwnProperty(key) ) continue;
                var value = params.data[key];
                if (Array.isArray(value)) {
                    for (var i = 0; i < value.length; i++) {
                        urlParams.append(key + '[]', value[i]);
                    }
                } else {
                    urlParams.append(key, value);
                }
            }
            fetchOptions.body = urlParams.toString();
        }

        fetch(params.url, fetchOptions)
            .then(function(response) {
                if ( ! response.ok ) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(data) {
                if (typeof params.success === 'function') params.success(data);
            })
            .catch(function(err) {
                if (typeof params.error === 'function') params.error(err);
            });
    }

    /**
     * Register shipments via AJAX.
     *
     * @param {object} config
     * @param {string} config.ajax_url - AJAX endpoint URL
     * @param {string} config.nonce - Security nonce
     * @param {Array}  config.ids - Order IDs to register
     * @param {object} config.translations - Translation strings:
     *   {string} translations.registering_shipments
     *   {string} translations.left_actions - Contains %d placeholder
     *   {string} translations.register_completed
     *   {string} translations.check_fail
     *   {string} translations.error_unknown
     * @param {object} [config.callbacks] - Optional UI callbacks:
     *   {function} callbacks.onStart - Called before AJAX request
     *   {function} callbacks.onError - Called on error (receives error message)
     *   {function} callbacks.onComplete - Called when registration fully completes
     */
    function registerShipments(config) {
        var translations = config.translations || {};
        var callbacks = config.callbacks || {};

        if (typeof callbacks.onStart === 'function') {
            callbacks.onStart();
        }

        itellaPopup.show(
            translations.registering_shipments || 'Registering shipments...',
            'warning', false, 0, true
        );

        ajax({
            url: config.ajax_url,
            method: 'POST',
            data: {
                action: 'bulk_register_shipments',
                ids: config.ids,
                nonce: config.nonce
            },
            success: function(response) {
                if (response.status === 'error') {
                    var errorMsg = response.msg || translations.error_unknown || 'Error';
                    itellaPopup.show(errorMsg, 'error');
                    if (typeof callbacks.onError === 'function') callbacks.onError(errorMsg);
                    return;
                }

                if (response.status === 'notice' && response.hasOwnProperty('values')) {
                    itellaPopup.show(buildMessage(response.msg, response.values), 'warning');
                }

                if (response.status === 'notice' && response.hasOwnProperty('order_ids') && response.order_ids) {
                    checkCrons(config);
                }
            },
            error: function(err) {
                var errorMsg = err.message || translations.error_unknown || 'Error';
                itellaPopup.show(errorMsg, 'error');
                if (typeof callbacks.onError === 'function') callbacks.onError(errorMsg);
            }
        });
    }

    function checkCrons(config) {
        var translations = config.translations || {};
        var callbacks = config.callbacks || {};

        ajax({
            url: config.ajax_url,
            method: 'POST',
            data: {
                action: 'itella_check_ongoing_registrations',
                nonce: config.nonce
            },
            success: function(response) {
                if (response.completed) {
                    itellaPopup.show(
                        translations.register_completed || 'Registration complete',
                        'success'
                    );
                    if (typeof callbacks.onComplete === 'function') callbacks.onComplete();
                } else {
                    if (response.actions_left && translations.left_actions) {
                        var msg = (translations.registering_shipments || '') + '<br/>';
                        msg += translations.left_actions.replace('%d', response.actions_left);
                        itellaPopup.show(msg, 'warning', false, 0, true);
                    }
                    setTimeout(function() {
                        checkCrons(config);
                    }, 3000);
                }
            },
            error: function(err) {
                var errorMsg = translations.check_fail || err.message || translations.error_unknown || 'Error';
                itellaPopup.show(errorMsg, 'error');
                if (typeof callbacks.onError === 'function') callbacks.onError(errorMsg);
            }
        });
    }

    return {
        register: registerShipments,
        buildMessage: buildMessage
    };
})();
