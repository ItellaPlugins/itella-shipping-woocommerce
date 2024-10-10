# Smartpost Itella Shipping plugin

Shipping plugin for Woocommerce to use with Smartpost Itella shipping methods

## Features

* Smartpost Itella Pickup Points and Courier shipping methods.
* Manage shipments:
    *   Register shipments 
    *   Generate labels 
    *   Generate manifests
    *   Call courier
* Review and edit shipping options in Edit Order page. 


## Installation

1. Download latest version of this plugin from [releases](https://github.com/ItellaPlugins/itella-shipping-woocommerce/releases) or pressing [here](https://github.com/ItellaPlugins/itella-shipping-woocommerce/releases/latest/download/itella-shipping.zip).
2. In the WordPress dashboard, go to:
 *Plugins* -> *Add New* -> *Upload plugin* -> *Choose file* (Select file and press open) -> *Install Now*
3. In *Plugins* section locate “Smartpost Itella Shipping”
4. Click on *Activate*

## Plugin hooks

```php
/* Adding a logo to Itella methods on the Checkout page if the "Show logo in Checkout" parameter is activated in the plugin settings */
add_filter('itella_checkout_method_label_add_logo', function($label, $image_url, $method_id, $org_label) { return $label; }, 10, 4);
```
