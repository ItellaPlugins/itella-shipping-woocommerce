'use strict';
// TODO disable and re-enable previously selected extra services according to selected method
(() => {
    const itellaShippingOptionsLink = document.getElementById('itella-shipping-options');

    if (itellaShippingOptionsLink == null) return;

    itellaShippingOptionsLink.addEventListener('click', () => {

        // gather elements
        const itellaShippingMethod = document.getElementById('itella_shipping_method');
        const itellaPacketCount = document.getElementById('packet_count');
        const itellaCodEnable = document.getElementById('itella_cod_enabled');
        const itellaCodAmount = document.getElementById('itella_cod_amount');
        const itellaPickupPoints = document.getElementById('_pp_id');
        const itellaExtraServices = document.querySelectorAll('.itella_extra_services_cb');
        const itellaMultiParcelCb = document.getElementById('itella_multi_parcel');
        // const itellaMultiParcelField = document.querySelector('.itella_multi_parcel_field');

        // elements to show validation error
        const saveOrderButton = document.querySelector('.save_order');
        const wcNotices = document.getElementById('woocommerce-layout__primary');

        // multi parcel is always disabled
        disableElements(itellaMultiParcelCb);

        let itellaCodEnableTempValue = itellaCodEnable.value;
        let itellaCodAmountTempValue = itellaCodAmount.value;
        let itellaPickupPointsTempValue = itellaPickupPoints.value;

        // toggle active/disabled fields when selected pickup point or courier
        if (itellaShippingMethod.value === 'itella_pp') {
            disableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
            disableElements(...itellaExtraServices);

            // disable cod
            itellaCodEnable.value = 'no';
            itellaCodAmount.value = '-';

            // pp method doesnt allow more than one packet
            itellaPacketCount.value = '1';
            itellaMultiParcelCb.checked = false;
        }
        if (itellaShippingMethod.value === 'itella_c') {

            // set multi parcel
            itellaMultiParcelCb.checked = itellaPacketCount.value > 1;

            // check cod
            if (itellaCodEnable.value === 'no') {
                itellaCodAmount.value = '-';
                disableElements(itellaCodAmount);
            }

            // deselect pp and disable pp field
            itellaPickupPoints.value = '-';
            disableElements(itellaPickupPoints);
        }

        // force enable multi parcel
        itellaPacketCount.addEventListener('change', () => {
            itellaMultiParcelCb.checked = itellaPacketCount.value > 1;
        })

        // listen for shipping method change
        itellaShippingMethod.addEventListener('change', () => {
            if (itellaShippingMethod.value === 'itella_pp') {
                itellaPacketCount.value = '1';
                itellaMultiParcelCb.checked = false;
                // itellaMultiParcelField.classList.toggle('d-none');

                itellaCodEnable.value = 'no';
                itellaCodAmount.value = '-';

                // set previously selected pp
                itellaPickupPoints.value = itellaPickupPointsTempValue;

                disableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
                disableElements(...itellaExtraServices);
                enableElements(itellaPickupPoints);
            }
            if (itellaShippingMethod.value === 'itella_c') {
                itellaCodEnable.value = itellaCodEnableTempValue;
                itellaCodAmount.value = itellaCodAmountTempValue;

                itellaPickupPoints.value = '-';

                enableElements(itellaPacketCount, itellaCodEnable, itellaCodAmount);
                enableElements(...itellaExtraServices);
                disableElements(itellaPickupPoints);
            }
        })

        // save pp selection
        itellaPickupPoints.addEventListener('change', () => {
            itellaPickupPointsTempValue = itellaPickupPoints.value;
        })

        itellaCodEnable.addEventListener('change', () => {
            if (itellaCodEnable.value === 'no') {
                itellaCodAmount.value = '-';
                disableElements(itellaCodAmount);
            } else {
                itellaCodAmount.value = itellaCodAmountTempValue;
                enableElements(itellaCodAmount);
            }
        })

        // validate if pp is selected
        saveOrderButton.addEventListener('click', e => {
            if (itellaShippingMethod.value === 'itella_pp' && itellaPickupPoints.value === '-') {
                e.preventDefault();
                e.stopPropagation();

                const errorBox = document.createElement('div');
                const errorMessage = document.createElement('p');
                errorBox.classList.add('error');
                errorMessage.textContent = 'Select Pickup Point field is required';
                errorBox.appendChild(errorMessage);
                wcNotices.appendChild(errorBox);
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