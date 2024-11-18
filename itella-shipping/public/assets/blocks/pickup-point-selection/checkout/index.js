(()=>{"use strict";const e=window.wp.blocks,t=window.React,l=window.wp.components,i=(0,t.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},(0,t.createElement)("path",{"fill-rule":"evenodd","clip-rule":"evenodd",fill:"#005DC0",d:"M1.9000000000000004,12a10.1,10.1 0 1,0 20.2,0a10.1,10.1 0 1,0 -20.2,0"})),o=JSON.parse('{"apiVersion":2,"name":"itella-shipping/pickup-point-selection-checkout","version":"1.0.0","title":"Smartpost parcel locker selection","category":"woocommerce","description":"Allow to add components for Smartpost shipping method on Checkout page","parent":["woocommerce/checkout-shipping-methods-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","default":""}},"textdomain":"itella-shipping"}'),p=window.wp.blockEditor,n=(window.wc.wcSettings,window.wp.i18n),c={block_options:(0,n.__)("Block options","itella-shipping"),pickup_block_title:(0,n.__)("Parcel locker","itella-shipping"),pickup_select_field_default:(0,n.__)("Select a parcel locker","itella-shipping"),cart_pickup_info:(0,n.__)("You can choose the parcel locker on the Checkout page","itella-shipping"),checkout_pickup_info:(0,n.__)("Choose one of parcel lockers close to the address you entered","itella-shipping"),pickup_error:(0,n.__)("Please choose a parcel locker","itella-shipping"),mapping:{nothing_found:(0,n.__)("Nothing found","itella-shipping"),modal_header:(0,n.__)("Parcel lockers","itella-shipping"),selector_header:(0,n.__)("Parcel locker","itella-shipping"),workhours_header:(0,n.__)("Workhours","itella-shipping"),contacts_header:(0,n.__)("Contacts","itella-shipping"),search_placeholder:(0,n.__)("Enter postcode/address","itella-shipping"),select_pickup_point:(0,n.__)("Select a parcel locker","itella-shipping"),no_pickup_points:(0,n.__)("No locker to select","itella-shipping"),select_btn:(0,n.__)("select","itella-shipping"),back_to_list_btn:(0,n.__)("reset search","itella-shipping"),select_pickup_point_btn:(0,n.__)("Select parcel locker","itella-shipping"),no_information:(0,n.__)("No information","itella-shipping"),error_leaflet:(0,n.__)("Leaflet is required for Itella-Mapping","itella-shipping"),error_missing_mount_el:(0,n.__)("No mount supplied to itellaShipping","itella-shipping")}};(0,e.registerBlockType)(o,{icon:i,edit:({attributes:e,setAttributes:i})=>{const{text:o}=e,n=(0,p.useBlockProps)(),a=[{label:c.pickup_select_field_default,value:""}];return(0,t.createElement)("div",{...n,style:{display:"block"}},(0,t.createElement)(p.InspectorControls,null,(0,t.createElement)(l.PanelBody,{title:c.block_options},"Options for the block go here.")),(0,t.createElement)("div",null,(0,t.createElement)(p.RichText,{value:o||c.pickup_block_title,onChange:e=>i({text:e})})),(0,t.createElement)("div",null,(0,t.createElement)(l.Disabled,null,(0,t.createElement)(l.SelectControl,{options:a}))))},attributes:{}})})();