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

/*
(function ($) {
    'use strict';

    // const country = document.querySelector('select').value;
    console.log('SCRIPT START')
    // initItellaMap();
    console.log(document.querySelector('#calc_shipping_country').value);

    const itellaCourierMethod = document.getElementById('shipping_method_0_itella_c');
    const itellaPickupPointMethod = document.getElementById('shipping_method_0_itella_pp');
    // $( document.body ).trigger( 'updated_shipping_method' );
    initItellaMap();

    // init itella map in cart page
    $(document.body).on('updated_shipping_method', () => {
        // console.log('UPDATED SHIPPING METHOD');
        // console.log(document.querySelector('#calc_shipping_country').value);
        initItellaMap();
        // addItellaInitListener();
        // toggleItellaMethodsByCountry();
    });

    $(document.body).on('updated_wc_div', () => {
        // console.log('UPDATED WC DIV')
        // console.log(document.querySelector('#calc_shipping_country').value);
        // initItellaMap();
        // addItellaInitListener();
        // toggleItellaMethodsByCountry();
    });

    $(document.body).on('country_to_state_changed', () => {
        // console.log('COUNTRY TO STATE CHANGED')
        // console.log(document.querySelector('#calc_shipping_country').value);
        // initItellaMap();
        // addItellaInitListener();
        // toggleItellaMethodsByCountry();
    });


    // init itella map in checkout page
    $(document.body).on('updated_checkout', () => {
        initItellaMap();
    });

    // if LT/LV show itella shipping methods, otherwise don't
    function toggleItellaMethodsByCountry() {
        console.log(document.querySelector('#calc_shipping_country').value);
        if (document.querySelector('select').value !== 'LT' || document.querySelector('select').value !== 'LV') {
            $(itellaPickupPointMethod.parentElement).hide();
            $(itellaCourierMethod.parentElement).hide();
        } else {
            $(itellaPickupPointMethod.parentElement).show();
            $(itellaCourierMethod.parentElement).show();
        }
    }

    // init map if itella pp method selected and map is not initialized
    function initItellaMap() {
        if (document.getElementById('shipping_method_0_itella_pp').checked) {
            if (!document.querySelector('.itella-shipping-container')) {
                init();
            }
        }
    }

    function addItellaInitListener() {
        document.getElementById('shipping_method_0_itella_pp').addEventListener('change', () => {
            initItellaMap();
        })
    }

})(jQuery);
 */