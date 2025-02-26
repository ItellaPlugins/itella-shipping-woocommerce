(()=>{"use strict";const e=window.wc.blocksCheckout,t=window.React,i=window.wp.element,l=window.wp.data,o=window.wp.i18n,p={id:"itella-shipping",pickup_methods_keys:["pickup_point"]},s=()=>wcSettings&&wcSettings["itella-shipping-blocks_data"]?wcSettings["itella-shipping-blocks_data"]:{},a=JSON.parse('{"apiVersion":2,"name":"itella-shipping/pickup-point-selection-cart","version":"1.0.1","title":"Smartposti parcel locker information","category":"woocommerce","description":"Allow to add components for Smartposti shipping method on Cart page","parent":["woocommerce/cart-order-summary-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false,"lock":false},"attributes":{"lock":{"type":"object","default":{"remove":false,"move":false}}},"textdomain":"itella-shipping"}');(0,e.registerCheckoutBlock)({metadata:a,component:({className:e})=>{const[a,n]=(0,i.useState)(!1),[r,c]=(0,i.useState)([]),[_,h]=(0,i.useState)(""),[u,g]=(0,i.useState)(!1),d=(0,l.useSelect)((e=>e("wc/store/cart").getCartData().shippingRates));return(0,i.useEffect)((()=>{d.length&&c((e=>{if(!e.length)return[];let t=[];for(let i=0;i<e.length;i++)if(e[i].shipping_rates)for(let l=0;l<e[i].shipping_rates.length;l++)e[i].shipping_rates[l].rate_id&&t.push(e[i].shipping_rates[l]);return t})(d))}),[d]),(0,i.useEffect)((()=>{if(r.length)for(let e=0;e<r.length;e++)r[e].selected&&_!=r[e].rate_id&&h(r[e].rate_id)}),[r]),(0,i.useEffect)((()=>{""!=_.trim()&&(((e,t=!1)=>{let i=s();if("methods"in i)for(let l in i.methods)if(i.methods[l]==e&&(!t||t&&p.pickup_methods_keys.includes(l)))return!0;return!1})(_,!0)?g(!0):g(!1))}),[_]),u?(0,t.createElement)("div",{className:"wc-block-components-totals-wrapper"},(0,t.createElement)("span",{className:"wc-block-components-totals-item"},(e=>{const t=((e=!0)=>{let t={block_options:(0,o.__)("Block options","itella-shipping"),pickup_block_title:(0,o.__)("Parcel locker","itella-shipping"),pickup_select_field_default:(0,o.__)("Select a parcel locker","itella-shipping"),cart_pickup_info:(0,o.__)("You can choose the parcel locker on the Checkout page","itella-shipping"),checkout_pickup_info:(0,o.__)("Choose one of parcel lockers close to the address you entered","itella-shipping"),pickup_error:(0,o.__)("Please choose a parcel locker","itella-shipping"),mapping:{nothing_found:(0,o.__)("Nothing found","itella-shipping"),modal_header:(0,o.__)("Parcel lockers","itella-shipping"),selector_header:(0,o.__)("Parcel locker","itella-shipping"),workhours_header:(0,o.__)("Workhours","itella-shipping"),contacts_header:(0,o.__)("Contacts","itella-shipping"),search_placeholder:(0,o.__)("Enter postcode/address","itella-shipping"),select_pickup_point:(0,o.__)("Select a parcel locker","itella-shipping"),no_pickup_points:(0,o.__)("No locker to select","itella-shipping"),select_btn:(0,o.__)("select","itella-shipping"),back_to_list_btn:(0,o.__)("reset search","itella-shipping"),select_pickup_point_btn:(0,o.__)("Select parcel locker","itella-shipping"),no_information:(0,o.__)("No information","itella-shipping"),error_leaflet:(0,o.__)("Leaflet is required for Itella-Mapping","itella-shipping"),error_missing_mount_el:(0,o.__)("No mount supplied to itellaShipping","itella-shipping")}};if(!e){const e=s();return"txt"in e?e.txt:{}}return t})(!1),i=Array.isArray(e)?e:[e];let l=t;for(const e of i){if(!l||"object"!=typeof l||!(e in l))return i[i.length-1];l=l[e]}return l})("cart_pickup_info"))):(0,t.createElement)(t.Fragment,null)}})})();