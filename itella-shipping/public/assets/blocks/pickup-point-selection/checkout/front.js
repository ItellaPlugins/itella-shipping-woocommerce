(()=>{"use strict";const e=window.wc.blocksCheckout,t=window.React,i=window.wp.element,s=window.wp.data,o=window.wp.components,a=window.wp.i18n,l={block_options:(0,a.__)("Block options","itella-shipping"),pickup_block_title:(0,a.__)("Pickup point","itella-shipping"),pickup_select_field_default:(0,a.__)("Select a pickup point","itella-shipping"),cart_pickup_info:(0,a.__)("You can choose the pickup point on the Checkout page","itella-shipping"),checkout_pickup_info:(0,a.__)("Choose one of pickup points close to the address you entered","itella-shipping"),pickup_error:(0,a.__)("Please choose a pickup point","itella-shipping")},n={id:"itella-shipping",pickup_methods_keys:["pickup_point"]},r=()=>wcSettings&&wcSettings["itella-shipping-blocks_data"]?wcSettings["itella-shipping-blocks_data"]:{},c=(e,t=!1)=>{let i=r();if("methods"in i)for(let s in i.methods)if(i.methods[s]==e&&(!t||t&&n.pickup_methods_keys.includes(s)))return!0;return!1},p=JSON.parse('{"apiVersion":2,"name":"itella-shipping/pickup-point-selection-checkout","version":"0.0.1","title":"Smartpost pickup point selection","category":"woocommerce","description":"Allow to add components for Smartpost shipping method on Checkout page","parent":["woocommerce/checkout-shipping-methods-block"],"supports":{"html":false,"align":false,"multiple":false,"reusable":false},"attributes":{"lock":{"type":"object","default":{"remove":true,"move":true}},"text":{"type":"string","default":""}},"textdomain":"itella-shipping"}');(0,e.registerCheckoutBlock)({metadata:p,component:({checkoutExtensionData:e,extension:a})=>{const p="itella_pickup_point",{setExtensionData:u}=e,[d,h]=(0,i.useState)([]),[f,g]=(0,i.useState)({country:"",address:"",city:"",postcode:""}),[_,m]=(0,i.useState)(""),[k,w]=(0,i.useState)([]),[y,b]=(0,i.useState)([]),[E,S]=(0,i.useState)(!1),[v,C]=(0,i.useState)(""),{setValidationErrors:O,clearValidationError:N}=(0,s.useDispatch)("wc/store/validation"),A=(0,s.useSelect)((e=>e("wc/store/validation").getValidationError(p))),{shippingRates:D,shippingAddress:j}=(0,s.useSelect)((e=>{const t=e("wc/store/cart");return{shippingRates:t.getCartData().shippingRates,shippingAddress:t.getCartData().shippingAddress}})),x=(e=>{const[t,s]=(0,i.useState)(e);return(0,i.useEffect)((()=>{const t=setTimeout((()=>{s(e)}),1500);return()=>{clearTimeout(t)}}),[e,1500]),t})(j);return(0,i.useEffect)((()=>{D.length&&h((e=>{if(!e.length)return[];let t=[];for(let i=0;i<e.length;i++)if(e[i].shipping_rates)for(let s=0;s<e[i].shipping_rates.length;s++)e[i].shipping_rates[s].rate_id&&t.push(e[i].shipping_rates[s]);return t})(D))}),[D]),(0,i.useEffect)((()=>{if(j.country){let i={country:j.country,address:j?.address_1||"",city:j?.city||"",postcode:j?.postcode||""};e=f,t=i,JSON.stringify(e)!==JSON.stringify(t)&&g(i)}var e,t}),[x]),(0,i.useEffect)((()=>{if(d.length)for(let e=0;e<d.length;e++)d[e].selected&&_!=d[e].rate_id&&m(d[e].rate_id)}),[d]),(0,i.useEffect)((()=>{""!=_.trim()&&(c(_,!0)?S(!0):S(!1))}),[_]),(0,i.useEffect)((()=>{E&&""!==f.country&&(async e=>{try{const t=r(),i=await fetch(`${t.locations_url}/locations${e}.json`,{method:"HEAD"});if(!i.ok)throw new Error(`File not found: ${i.status}`);const s=await fetch(`${t.locations_url}/locations${e}.json`);if(!s.ok)throw new Error(`Error: ${s.status}`);return await s.json()}catch(e){console.error("Failed to get locations.",e)}return null})(f.country).then((e=>{w((e=>{let t=r(),i=Array.isArray(e)?JSON.parse(JSON.stringify(e)):[];if(!t.hasOwnProperty("locations_filter"))return i;let s=i.length;for(;s--;)if(Object.hasOwn(i[s],"capabilities"))for(let e=0;e<i[s].capabilities.length;e++)"yes"==t.locations_filter.exclude_outdoors&&"outdoors"==i[s].capabilities[e].name&&"OUTDOORS"==i[s].capabilities[e].value&&i.splice(s,1);return i})(e))}))}),[E,f]),(0,i.useEffect)((()=>{let e=((e,t)=>{let i=[];if(e.length)for(let t=0;t<e.length;t++)i.push({id:(s=e[t]).id,pupCode:s?.pupCode||"",publicName:s?.publicName||"-",address:s?.address?.address||"",city:s?.address?.municipality||"",postcode:s?.address?.postalCode||""});var s;return i.sort(((e,i)=>e.city.localeCompare(i.city,t.toLowerCase(),{sensitivity:"base"}))).reduce(((e,t)=>{let i=t.city.trim();return e[i]||(e[i]=[]),e[i].push(t),e}),{})})(k,f.country),t=[];t["-"]=[{label:l.pickup_select_field_default,value:""}];for(let i in e){t[i]||(t[i]=[]);for(let s=0;s<e[i].length;s++)t[i].push({label:e[i][s].publicName+", "+e[i][s].address+", "+e[i][s].city,value:e[i][s].id})}b(t)}),[k]),(0,i.useEffect)((()=>{p&&N(p),""!=_.trim()&&c(_,!0)&&(u(n.id,"selected_pickup_id",v),u(n.id,"selected_rate_id",_),""===v&&O({[p]:{message:l.pickup_error,hidden:!1}}))}),[u,_,v]),E?(0,t.createElement)("div",{className:"itella-shipping-container"},(0,t.createElement)(o.SelectControl,{id:"itella-pickup-points-list",label:l.pickup_block_title,value:v,onChange:e=>C(e)},Object.keys(y).map((e=>"-"===e?(0,t.createElement)("option",{key:"default",value:""},y[e][0].label):(0,t.createElement)("optgroup",{key:e,label:e},y[e].map((e=>(0,t.createElement)("option",{key:e.value,value:e.value},e.label))))))),A?.hidden||""!==v?null:(0,t.createElement)("div",{className:"wc-block-components-validation-error"},(0,t.createElement)("span",null,A?.message))):(0,t.createElement)(t.Fragment,null)}})})();