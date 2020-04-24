(function( $ ) {
    'use strict';

    // init itella map in cart page
    $(document.body).on('updated_shipping_method', () => {
         if (document.getElementById('shipping_method_0_itella_pp').checked) {
            if(!document.querySelector('.itella-shipping-container')) {
                init();
            }
         }
    });

    // init itella map in checkout page
    $(document.body).on('updated_checkout', () => {
        if (document.getElementById('shipping_method_0_itella_pp').checked) {
            if(!document.querySelector('.itella-shipping-container')) {
                init();
            }
        }
    });

})( jQuery );