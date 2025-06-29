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
    var add_to;

    if (itellaPpShippingMethod.length) {
        add_to = itellaPpShippingMethod[0].nextElementSibling;
        if (itellaPpShippingMethod[0].nextElementSibling === null) {
            add_to = itellaPpShippingMethod[0].closest('li');
        }
    } else {
        var elem = jQuery('#itella-pickup-block');
        add_to = elem[0];
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
        .setCountry(itellaGetCountry())
        .setLocations(terminals, true)
        .registerCallback(function (manual) {
            // access itella class
            itellaSetPpStorageValues(this.selectedPoint.id, this.selectedPoint.pupCode);
            updateHiddenPpIdInput(this.selectedPoint.id);
            updateHiddenPpCodeInput(this.selectedPoint.pupCode);
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

function itellaGetPpStorageValues() {
    const storedPickupPoint = localStorage.getItem('pickupPoint');

    if (storedPickupPoint) {
        try {
            const pickupPoint = JSON.parse(storedPickupPoint);
            if (
                pickupPoint.timestamp &&
                pickupPoint.id &&
                pickupPoint.pupCode &&
                (Date.now() - parseInt(pickupPoint.timestamp, 10) <= 1000 * 60 * 60 * 24) //Use values only if not older than 24 h
            ) {
                return pickupPoint;
            }
        } catch (e) {
            // Do nothing - wrong JSON
        }
    }

    return itellaSetPpStorageValues('', '');
}

function itellaSetPpStorageValues(id, pupCode) {
    const pickupPoint = {
        timestamp: Date.now(),
        id: id,
        pupCode: pupCode
    };

    localStorage.setItem('pickupPoint', JSON.stringify(pickupPoint));
    return pickupPoint;
}

function itellaGetCountry() {
    let useShippingAddress = document.getElementById('ship-to-different-address-checkbox');
    if ( typeof(useShippingAddress) != 'undefined' && useShippingAddress != null && useShippingAddress.checked ) {
        if ( document.querySelector('#shipping_country') ) return document.querySelector('#shipping_country').value;
    }
    if ( document.querySelector('#billing_country') ) return document.querySelector('#billing_country').value;
    if ( document.querySelector('#calc_shipping_country') ) return document.querySelector('#calc_shipping_country').value;

    return document.querySelector('#itella_shipping_country').value;
}

function loadJson() {
    let json = JSON.parse(this.responseText);
    let locations = itellaFilterLocations(json);

    this.itella.setLocations(locations, true);

    // select from list by pickup point ID
    const pickupPoint = itellaGetPpStorageValues();
    itella.setSelection(pickupPoint.id, false);
}

function itellaFilterLocations(locations_json) {
    let locations = Array.isArray(locations_json) ? JSON.parse(JSON.stringify(locations_json)) : [];

    let i = locations.length;
    while (i--) {
        if (! Object.hasOwn(locations[i], 'capabilities')) {
            continue;
        }
        for (let j = 0; j < locations[i].capabilities.length; j++) {
            if (variables.locationsFilter.exclude_outdoors == 'yes' && locations[i].capabilities[j].name == 'outdoors' && locations[i].capabilities[j].value == 'OUTDOORS') {
                locations.splice(i, 1);
            }
        }
    }

    return locations;
}

function setHiddenPpIdInput() {
    const chosenPpIdElement = document.querySelector('#itella-chosen-point-id');
    if (chosenPpIdElement) {
        chosenPpIdElement.remove();
    }
    const chosenPpCodeElement = document.querySelector('#itella-chosen-point-code');
    if (chosenPpCodeElement) {
        chosenPpCodeElement.remove();
    }

    var radio = jQuery('input[value="itella_pp"]');
    if (!radio.length) {
        radio = jQuery('#itella-pickup-block');
    }
    const ppIdElement = document.createElement('input');
    ppIdElement.setAttribute('type', 'hidden');
    ppIdElement.setAttribute('name', 'itella-chosen-point-id');
    ppIdElement.setAttribute('id', 'itella-chosen-point-id');
    ppIdElement.setAttribute('autocomplete', 'off');
    radio[0].parentElement.appendChild(ppIdElement);
    const ppCodeElement = document.createElement('input');
    ppCodeElement.setAttribute('type', 'hidden');
    ppCodeElement.setAttribute('name', 'itella-chosen-point-code');
    ppCodeElement.setAttribute('id', 'itella-chosen-point-code');
    ppCodeElement.setAttribute('autocomplete', 'off');
    radio[0].parentElement.appendChild(ppCodeElement);
    const storageValues = itellaGetPpStorageValues();
    ppIdElement.value = storageValues.id;
    ppCodeElement.value = storageValues.pupCode;
}

function updateHiddenPpIdInput(ppId) {
    const ppIdElement = document.getElementById('itella-chosen-point-id');
    ppIdElement.value = ppId;
}

function updateHiddenPpCodeInput(ppCode) {
    const ppCodeElement = document.getElementById('itella-chosen-point-code');
    ppCodeElement.value = ppCode;
}

function updateDropdown() {
    var post_value = document.querySelector('#shipping_postcode') ?
                     document.querySelector('#shipping_postcode').value :
                     (document.querySelector('#billing_postcode') ?
                     document.querySelector('#billing_postcode').value : '');
    itella.UI[itella.isModal ? 'modal' : 'container'].getElementsByClassName('search-input')[0].value = post_value;
    itella.searchNearest(""+post_value);
}