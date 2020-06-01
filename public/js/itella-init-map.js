(function( $ ) {
    'use strict';

    // init itella map in cart page
    $(document.body).on('updated_shipping_method', () => {
        // console.log('updated_shipping_method')
        if ($('#shipping_method_0_itella_pp').is(':checked')) {
            if(!$('.itella-shipping-container').length) {
                itella_init();
            }
         }
    });

    // init itella map in checkout page
    $(document.body).on('updated_checkout', () => {
        // console.log('updated_checkout')
        if ($('#shipping_method_0_itella_pp').is(':checked')) {
            if(!$('.itella-shipping-container').length) {
                itella_init();
            }
        }
    });

    $(document.body).on('updated_wc_div', () => {
        // console.log('updated_wc_div')
        if ($('#shipping_method_0_itella_pp').is(':checked')) {
            if(!$('.itella-shipping-container').length) {
                itella_init();
            }
        }
    });

})( jQuery );