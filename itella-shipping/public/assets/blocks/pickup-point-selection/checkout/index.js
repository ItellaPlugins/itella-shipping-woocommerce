(()=>{"use strict";const e=window.wp.blocks,t=window.React,o=window.wp.components,i=(0,t.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24"},(0,t.createElement)("path",{"fill-rule":"evenodd","clip-rule":"evenodd",fill:"#005DC0",d:"M1.9000000000000004,12a10.1,10.1 0 1,0 20.2,0a10.1,10.1 0 1,0 -20.2,0"})),l=JSON.parse('{"apiVersion":2,"name":"itella-shipping/pickup-point-selection-checkout","version":"0.0.1","title":"Smartpost pickup point selection","category":"woocommerce","description":"Allow to add components for Smartpost shipping method on Checkout page","parent":["woocommerce/checkout-shipping-methods-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","default":""}},"textdomain":"itella-shipping"}'),p=window.wp.blockEditor,n=(window.wc.wcSettings,window.wp.i18n),c={block_options:(0,n.__)("Block options","itella-shipping"),pickup_block_title:(0,n.__)("Pickup point","itella-shipping"),pickup_select_field_default:(0,n.__)("Select a pickup point","itella-shipping"),cart_pickup_info:(0,n.__)("You can choose the pickup point on the Checkout page","itella-shipping"),checkout_pickup_info:(0,n.__)("Choose one of pickup points close to the address you entered","itella-shipping"),pickup_error:(0,n.__)("Please choose a pickup point","itella-shipping")};(0,e.registerBlockType)(l,{icon:i,edit:({attributes:e,setAttributes:i})=>{const{text:l}=e,n=(0,p.useBlockProps)(),s=[{label:c.pickup_select_field_default,value:""}];return(0,t.createElement)("div",{...n,style:{display:"block"}},(0,t.createElement)(p.InspectorControls,null,(0,t.createElement)(o.PanelBody,{title:c.block_options},"Options for the block go here.")),(0,t.createElement)("div",null,(0,t.createElement)(p.RichText,{value:l||c.pickup_block_title,onChange:e=>i({text:e})})),(0,t.createElement)("div",null,(0,t.createElement)(o.Disabled,null,(0,t.createElement)(o.SelectControl,{options:s}))))},attributes:{}})})();