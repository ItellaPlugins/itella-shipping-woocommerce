'use strict';
jQuery( document ).ready(function($) {
    $(document.body).on('updated_checkout', function() {
        if ( ! $('input[value="itella_pp"]').length) {
            if ($('.shipping_method').length) {
                if ($('.shipping_method').val() === 'itella_pp' && ! $('#itella-pickup-block').length ) {
                    $('<div id="itella-pickup-block"></div>').insertAfter('.shipping_method');
                    itella_init();
                }
            }
        }
    });
    $(document.body).on('updated_checkout', function() {
        var itella_descriptions = $(".itella-shipping-description");
        for (var i=0;i<itella_descriptions.length;i++) {
            if ($(itella_descriptions[i]).closest("li").find("input.shipping_method").is(':checked')) {
                $(itella_descriptions[i]).show();
            } else {
                $(itella_descriptions[i]).hide();
            }
        }
    });
});

if (jQuery('input[name^="shipping_method"]:checked').val() === 'itella_pp') {
    itella_init();
}

function itella_init() {
    let itellaPpShippingMethod = jQuery('input[value="itella_pp"]');

    if (itellaPpShippingMethod.length) {
        var add_to = itellaPpShippingMethod[0].nextSibling;
    } else {
        var elem = jQuery('#itella-pickup-block');
        var add_to = elem[0];
    }

    window.itella = new itellaMapping(add_to);

    var show_map = (variables.show_style == 'map') ? true : false;
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
        .init(show_map)
        .setCountry(
            document.querySelector('#billing_country') ?
            document.querySelector('#billing_country').value :
            ( document.querySelector('#calc_shipping_country') ?
              document.querySelector('#calc_shipping_country').value :
              document.querySelector('#itella_shipping_country').value )
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

    updateDropdown();
}

function loadJson() {
    let json = JSON.parse(this.responseText);
    this.itella.setLocations(json, true);

    // select from list by pickup point ID
    if (localStorage.getItem('pickupPoint')) {
        const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
        itella.setSelection(pickupPoint.id, false);
    }
}

function setHiddenPpIdInput() {
    const chosenPpIdElement = document.querySelector('#itella-chosen-point-id');
    if (chosenPpIdElement) {
        chosenPpIdElement.remove();
    }

    var radio = jQuery('input[value="itella_pp"]');
    if (!radio.length) {
        radio = jQuery('#itella-pickup-block');
    }
    const ppIdElement = document.createElement('input');
    ppIdElement.setAttribute('type', 'hidden');
    ppIdElement.setAttribute('name', 'itella-chosen-point-id');
    ppIdElement.setAttribute('id', 'itella-chosen-point-id');
    radio[0].parentElement.appendChild(ppIdElement);
    if (localStorage.getItem('pickupPoint')) {
        const pickupPoint = JSON.parse(localStorage.getItem('pickupPoint'));
        ppIdElement.value = pickupPoint.id;
    }
}

function updateHiddenPpIdInput(ppId) {
    const ppIdElement = document.getElementById('itella-chosen-point-id');
    ppIdElement.value = ppId;
}

function updateDropdown() {
    var post_value = document.querySelector('#shipping_postcode') ?
                     document.querySelector('#shipping_postcode').value :
                     (document.querySelector('#billing_postcode') ?
                     document.querySelector('#billing_postcode').value : '');
    itella.UI[itella.isModal ? 'modal' : 'container'].getElementsByClassName('search-input')[0].value = post_value;
    itella.searchNearest(""+post_value);
}