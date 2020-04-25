(function( $ ) {
    'use strict';

    // init itella map in cart page
    $(document.body).on('updated_shipping_method', () => {
        if ($('#shipping_method_0_itella_pp').is(':checked')) {
            if(!$('.itella-shipping-container').val()) {
                init();
            }
         }
    });

    // init itella map in checkout page
    $(document.body).on('updated_checkout', () => {
        if ($('#shipping_method_0_itella_pp').is(':checked')) {
            if(!$('.itella-shipping-container').val()) {
                init();
            }
        }
    });

})( jQuery );