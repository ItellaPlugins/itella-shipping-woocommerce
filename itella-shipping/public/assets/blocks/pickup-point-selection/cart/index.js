(()=>{"use strict";const e=window.wp.blocks,l=window.React,i=(window.wp.components,(0,l.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},(0,l.createElement)("path",{"fill-rule":"evenodd","clip-rule":"evenodd",fill:"#005DC0",d:"M1.9000000000000004,12a10.1,10.1 0 1,0 20.2,0a10.1,10.1 0 1,0 -20.2,0"}))),t=JSON.parse('{"apiVersion":2,"name":"itella-shipping/pickup-point-selection-cart","version":"1.0.0","title":"Smartpost parcel locker information","category":"woocommerce","description":"Allow to add components for Smartpost shipping method on Cart page","parent":["woocommerce/cart-order-summary-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false,"lock":false},"attributes":{"lock":{"type":"object","default":{"remove":false,"move":false}}},"textdomain":"itella-shipping"}'),o=window.wp.blockEditor,p=window.wp.i18n,a={block_options:(0,p.__)("Block options","itella-shipping"),pickup_block_title:(0,p.__)("Parcel locker","itella-shipping"),pickup_select_field_default:(0,p.__)("Select a parcel locker","itella-shipping"),cart_pickup_info:(0,p.__)("You can choose the parcel locker on the Checkout page","itella-shipping"),checkout_pickup_info:(0,p.__)("Choose one of parcel lockers close to the address you entered","itella-shipping"),pickup_error:(0,p.__)("Please choose a parcel locker","itella-shipping"),mapping:{nothing_found:(0,p.__)("Nothing found","itella-shipping"),modal_header:(0,p.__)("Parcel lockers","itella-shipping"),selector_header:(0,p.__)("Parcel locker","itella-shipping"),workhours_header:(0,p.__)("Workhours","itella-shipping"),contacts_header:(0,p.__)("Contacts","itella-shipping"),search_placeholder:(0,p.__)("Enter postcode/address","itella-shipping"),select_pickup_point:(0,p.__)("Select a parcel locker","itella-shipping"),no_pickup_points:(0,p.__)("No locker to select","itella-shipping"),select_btn:(0,p.__)("select","itella-shipping"),back_to_list_btn:(0,p.__)("reset search","itella-shipping"),select_pickup_point_btn:(0,p.__)("Select parcel locker","itella-shipping"),no_information:(0,p.__)("No information","itella-shipping"),error_leaflet:(0,p.__)("Leaflet is required for Itella-Mapping","itella-shipping"),error_missing_mount_el:(0,p.__)("No mount supplied to itellaShipping","itella-shipping")}};(0,e.registerBlockType)(t,{icon:i,edit:({attributes:e,setAtrributes:i})=>{const t=(0,o.useBlockProps)();return(0,l.createElement)("div",{...t,style:{display:"block"}},(0,l.createElement)("div",{className:"wc-block-components-totals-wrapper"},(0,l.createElement)("span",{clallName:"wc-block-components-totals-item"},a.cart_pickup_info)))},save:()=>(0,l.createElement)("div",{...o.useBlockProps.save()})})})();