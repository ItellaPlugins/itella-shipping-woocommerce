import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin('itella-shipping', {
    render,
    scope: 'woocommerce-checkout',
});
