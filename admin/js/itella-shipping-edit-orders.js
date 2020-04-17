'use strict';

(() => {
    const itellaShippingOptionsLink = document.getElementById('itella-shipping-options');

    itellaShippingOptionsLink.addEventListener('click', () => {

        // gather elements
        const itellaShippingMethod = document.getElementById('itella_shipping_method');
        const itellaPacketCount = document.getElementById('packet_count');
        const itellaCodEnable = document.getElementById('itella_cod_enabled');
        const itellaCodAmount = document.getElementById('itella_cod_amount');
        const itellaPickupPoints = document.getElementById('itella_pickup_points');
        const itellaExtraServices = document.querySelectorAll('.itella_extra_services_cb');
        const itellaMultiParcelCb = document.getElementById('itella_multi_parcel');
        const itellaMultiParcelField = document.querySelector('.itella_multi_parcel_field');

        disableElements(itellaMultiParcelCb); // always disabled
        let itellaCodEnableTempValue = itellaCodEnable.value;
        let itellaCodAmountTempValue = itellaCodAmount.value;

        // toggle active/disabled fields when selected pickup point or courier
        if (itellaShippingMethod.value === 'itella_pp') {
            disableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
            disableElements(...itellaExtraServices);

            itellaCodEnable.value = 'no';
            itellaCodAmount.value = '-';

            itellaPacketCount.value = '1';
            itellaMultiParcelField.classList.toggle('d-none');
        }
        if (itellaShippingMethod.value === 'itella_c') {
            disableElements(itellaPickupPoints);
        }

        // force enable multi parcel
        itellaPacketCount.addEventListener('change', () => {
            if (itellaPacketCount.value > 1) {
                itellaMultiParcelCb.checked = true;
            }
            if (itellaPacketCount.value > 1 && itellaMultiParcelField.classList.contains('d-none')) {
                itellaMultiParcelField.classList.toggle('d-none');
            }
            if (itellaPacketCount.value <= 1) {
                itellaMultiParcelCb.checked = false;
                itellaMultiParcelField.classList.toggle('d-none');
            }
        })

        // listen for shipping method change
        itellaShippingMethod.addEventListener('change', () => {
            if (itellaShippingMethod.value === 'itella_pp') {
                itellaPacketCount.value = '1';
                itellaMultiParcelField.classList.toggle('d-none');

                itellaCodEnable.value = 'no';
                itellaCodAmount.value = '-';

                disableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
                disableElements(...itellaExtraServices);
                enableElements(itellaPickupPoints);
            }
            if (itellaShippingMethod.value === 'itella_c') {
                itellaCodEnable.value = itellaCodEnableTempValue;
                itellaCodAmount.value = itellaCodAmountTempValue;

                enableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
                enableElements(...itellaExtraServices);
                disableElements(itellaPickupPoints);
            }
        })

    })

    function disableElements(...elements) {
        elements.forEach(element =>{
            element.disabled = true;
            element.style.cursor = 'not-allowed';
        })
    }

    function enableElements(...elements) {
        elements.forEach(element =>{
            element.disabled = false;
            element.style.cursor = 'initial';
        })
    }
})();