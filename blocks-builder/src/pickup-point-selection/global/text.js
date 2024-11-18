import { __ } from '@wordpress/i18n';

//export const txt = wcSettings["itella_data"].txt; //Temporary solution while not clear how use @wordpress/i18n

export const txt/*_i18n*/ = {
    block_options: __('Block options', 'itella-shipping'),
    pickup_block_title: __('Parcel locker', 'itella-shipping'),
    pickup_select_field_default: __('Select a parcel locker', 'itella-shipping'),
    cart_pickup_info: __('You can choose the parcel locker on the Checkout page', 'itella-shipping'),
    checkout_pickup_info: __('Choose one of parcel lockers close to the address you entered', 'itella-shipping'),
    pickup_error: __('Please choose a parcel locker', 'itella-shipping'),
    mapping: {
        nothing_found: __('Nothing found', 'itella-shipping'),
        modal_header: __('Parcel lockers', 'itella-shipping'),
        selector_header: __('Parcel locker', 'itella-shipping'),
        workhours_header: __('Workhours', 'itella-shipping'),
        contacts_header: __('Contacts', 'itella-shipping'),
        search_placeholder: __('Enter postcode/address', 'itella-shipping'),
        select_pickup_point: __('Select a parcel locker', 'itella-shipping'),
        no_pickup_points: __('No locker to select', 'itella-shipping'),
        select_btn: __('select', 'itella-shipping'),
        back_to_list_btn: __('reset search', 'itella-shipping'),
        select_pickup_point_btn: __('Select parcel locker', 'itella-shipping'),
        no_information: __('No information', 'itella-shipping'),
        error_leaflet: __('Leaflet is required for Itella-Mapping', 'itella-shipping'),
        error_missing_mount_el: __('No mount supplied to itellaShipping', 'itella-shipping')
    }
};
