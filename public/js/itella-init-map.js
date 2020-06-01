(function( $ ) {
    'use strict';

    // init itella map in cart page
    $(document.body).on('updated_shipping_method', () => {
        mountItella();
    });

    $(document.body).on('updated_wc_div', () => {
        mountItella();
    });

    // init itella map in checkout page
    $(document.body).on('updated_checkout', () => {
        mountItella();
    });

    function mountItella() {
        if ($('input[name^="shipping_method"]:checked').val() === 'itella_pp') {
            $('.itella-shipping-container').remove();
            itella_init();
        }
    }

})( jQuery );