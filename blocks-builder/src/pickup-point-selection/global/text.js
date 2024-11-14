import { __ } from '@wordpress/i18n';

//export const txt = wcSettings["itella_data"].txt; //Temporary solution while not clear how use @wordpress/i18n

export const txt/*_i18n*/ = {
    block_options: __('Block options', 'itella-shipping'),
    pickup_block_title: __('Pickup point', 'itella-shipping'),
    pickup_select_field_default: __('Select a pickup point', 'itella-shipping'),
    cart_pickup_info: __('You can choose the pickup point on the Checkout page', 'itella-shipping'),
    checkout_pickup_info: __('Choose one of pickup points close to the address you entered', 'itella-shipping'),
    pickup_error: __('Please choose a pickup point', 'itella-shipping')
};
