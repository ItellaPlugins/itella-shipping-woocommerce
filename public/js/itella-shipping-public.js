'use strict';

if (document.getElementById('shipping_method_0_itella_pp').checked) {
    itella_init();
}

function itella_init() {
    window.itella = new itellaMapping(document.getElementById('shipping_method_0_itella_pp').nextSibling);

    let terminals = [];
    itella
        .setImagesUrl(variables.imagesUrl)
        .setStrings({
            nothing_found: variables.translations.nothing_found,
            modal_header: variables.translations.modal_header,
            selector_header: variables.translations.selector_header,
            workhours_header: variables.translations.workhours_header,
            contacts_header: variables.translations.contacts_header,
            search_placeholder: variables.translations.search_placeholder,
            select_pickup_point: variables.translations.select_pickup_point,
            no_pickup_points: variables.translations.no_pickup_points,
            select_btn: variables.translations.select_btn,
            back_to_list_btn: variables.translations.back_to_list_btn,
            select_pickup_point_btn: variables.translations.select_pickup_point_btn,
            no_information: variables.translations.no_information,
            error_leaflet: variables.translations.error_leaflet,
            error_missing_mount_el: variables.translations.error_missing_mount_el
        })
        .init()
        .setCountry(
            document.querySelector('#billing_country') ?
            document.querySelector('#billing_country').value :
            document.querySelector('#calc_shipping_country').value
        )
        .setLocations(terminals, true)
        .registerCallback(function (manual) {
            // access itella class
            localStorage.setItem('pickupPoint', JSON.stringify({
                'id': this.selectedPoint.id,
            }));
            updateHiddenPpIdInput(this.selectedPoint.id);
        });

    // set ppID as hidden input
    setHiddenPpIdInput();

    // load locations
    var oReq = new XMLHttpRequest();
    // access itella class inside response handler
    oReq.itella = itella;
    oReq.addEventListener('loadend', loadJson);
    oReq.open('GET', `${variables.locationsUrl}locations${itella.country}.json`);
    oReq.send();
}

function loadJson() {
    let json = JSON.parse(this.responseText);
    // console.log(this)
    this.itella.setLocations(json, true);

    // select from list by pickup point ID
    if (localStorage.getItem('pickupPoint')) {
        const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
        itella.setSelection(pickupPoint.id, false);
    }
}

function setHiddenPpIdInput() {
    const radio = document.getElementById('shipping_method_0_itella_pp');
    const ppIdElement = document.createElement('input');
    ppIdElement.setAttribute('type', 'hidden');
    ppIdElement.setAttribute('name', 'itella-chosen-point-id');
    ppIdElement.setAttribute('id', 'itella-chosen-point-id');
    radio.parentElement.appendChild(ppIdElement);
    if (localStorage.getItem('pickupPoint')) {
        const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
        ppIdElement.value = pickupPoint.id;
    }
}

function updateHiddenPpIdInput(ppId) {
    const ppIdElement = document.getElementById('itella-chosen-point-id');
    ppIdElement.value = ppId;
}