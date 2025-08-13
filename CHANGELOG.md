# Changelog

### [Unreleased]
### Fixed
- fixed loading of the settings page design

### Improved
- the shipment registration popup, originally developed for newer WooCommerce versions, has now been made compatible with older versions of the orders page

### [1.6.6] - 2025-07-30
### Fixed
- removed developer trash

### [1.6.5] - 2025-07-29
### Improved
- added display of popup messages during shipments registration via bulk action in the WC orders page

### Updated
- itella-api library to v2.4.7

### [1.6.4] - 2025-07-11
### Fixed
- fixed the Sender ID of the courier invitation to use the plugin developer ID instead of the sender's company code

### Improved
- added display of popup messages during mass shipment registration from the Smartposti shipments page

### [1.6.3] - 2025-06-30
### Improved
- added error messages if the weight of a parcel exceeds 35 kg or the weight of all parcels of the shipment exceeds 150 kg

### [1.6.2] - 2025-06-26
### Fixed
- fixed to remove error message after successful shipment registration
- fixed so that when registering multi-parcel shipments, the order weight would be divided by the number of packages

### Updated
- itella-api library to v2.4.6

## [1.6.1] - 2025-06-18
### Improved
- it has been made that shipment registration execute via a cronjob when shipments are registered during a mass action

### Updated
- itella-api library to v2.4.5

## [1.6.0] - 2025-06-09
### Fixed
- disabled test parameter
- fixed pupCode value saved to order
- fixed not using a pickup point selected more than 24 hours ago

### Improved
- added the ability to specify an information message for the courier who arrives to pickup the shipments
- added the ability to specify the arrival date and time when calling a courier
- on the Woocommerce orders page, added the possibility to register shipments and download shipment labels via bulk action

### Changed
- updated the courier invitation function according to changes in the API library

### Updated
- itella-api library to v2.4.3

## [1.5.5] - 2025-04-03
### Fixed
- fixed loading of external scripts on the Checkout page so that they are loaded only on this page

## [1.5.4] - 2025-03-20
### Improved
- added use of "Address 2" field value when registering a shipment

### Changed
- changed name "Itella Smartpost" to "Smartposti"
- changed logo
- changed map colors

## [1.5.3] - 2025-01-16
### Improved
- added to automatically recognize Woocommerce COD payment method

## [1.5.2] - 2024-12-20
### Fixed
- fixed the pickup point display in emails

## [1.5.1] - 2024-12-02
### Fixed
- fixed that when registering a shipment, first take the shipping address phone number and if it is not available, then take the billing address phone number

### Updated
- itella-api library to v2.3.8.1

## [1.5.0] - 2024-11-20
### Improved
- added to make the plugin work on a block-based Checkout page

### Changed
- plugin files moved to separate folder in plugin Github repository
- created Changelog file is for plugin releases

## [1.4.6] - 2024-10-16
### Improved
- added the ability to display the logo next to the Itella shipping method label on the Cart/Checkout page

## [1.4.5] - 2024-09-16
### Fixed
- added descriptions of variables in classes that were not added
- fixed display of terminals by shipping country on Checkout page

### Improved
- improved "Smartpost shipments" page SQL query to reduce execution time
- added option to disable display of parcel lockers that have "Outdoors" parameter on Cart and Checkout pages

## [1.4.4.2] - 2024-03-18
### Fixed
- fixed bug from last update in Checkout pickup selection
- fixed pickup saving in order edit page

## [1.4.4.1] - 2024-02-20
### Fixed
- fixed bug from last update in order preview

## [1.4.4] - 2024-02-15
### Fixed
- fixed that the Itella plugin's front scripts would be loaded only on the Cart and Checkout pages

### Changed
- made it so that pupCode is used everywhere instead of the ID of the pick-up point
- unified Itella meta keys for Order meta data

### Improved
- created a separate class for working with WC Order Itella meta data

## [1.4.3] - 2023-11-23
### Fixed
- fixed error in "Smartpost shipments" page when parcel locker is not selected for the Order
- fixed not constantly trying to update location files for countries that don't have a parcel lockers
- fixed getting courier invitation email address

## [1.4.2] - 2023-10-10
### Fixed
- removed old courier call element
- fixed a error when an item in an order is deleted

### Improved
- it has been made possible to enable the COD service for the order with the parcel locker shipping method on the order editing page

### Changed
- changed Locations API URL

## [1.4.1] - 2023-08-02
### Fixed
- added weight to courier shipments
- fixed messages when calling a courier
- fixed weight number format error
- fixed tracking code filter reset in manifest page

### Improved
- added additional Smartpost meta data adding when creating an order, if it failed to add the first time
- added a ability to manually set Smartpost shipping method, when Smartpost data is missing in order
- added a ability to hide shipping methods if cart weight or size is to big
- created use of order billing address when order shipping address is empty
- the display of order statuses is unified with the display of Woocommerce statuses in tables
- the plugin is adapted to work with Woocommerce HPOS (prepared for Woocommerce 8)

## [1.4.0] - 2022-12-19
### Fixed
- added order weight conversion to kilograms if other weight units are used on the website
- fixed error message, when get error on label generation
- fixed settings page values after settings save
- added display of "Smartpost shipments" page for "Shop manager" user role

### Improved
- improved order registration ajax function
- Woocommerce submenu element "Smartpost shipments" moved after "Orders" element
- added courier delivery to almost all EU countries
- added a ability to change the name of the shipping method visible in the cart to a custom one

### Changed
- changed "Pickup point" to "Parcel locker"

### Updated
- itella-api library to v2.3.7

## [1.3.8] - 2022-06-23
### Fixed
- fixed error, when product in order is deleted
- fixed error, when all products in cart is virtual
- removed show of the shipping method, when cart weight out of "price by weight" interval

## [1.3.7] - 2022-04-19
### Fixed
- fixed order weight adding to shipment
- fixed params updating in order edit page
- fixed pickup points file getting

### Improved
- improved handling of plugin files on frontend pages

## [1.3.6] - 2022-02-23
### Fixed
- fixed pick-up point selection showing in some not standard themes

### Improved
- adapted for PHP 8.x

### Updated
- itella-api library to v2.3.5

## [1.3.5] - 2021-10-29
### Improved
- added pick-up point code saving in Order meta

## [1.3.4] - 2021-09-30
### Improved
- added COD service support to Pickup Point delivery method

### Updated
- itella-api library to v2.3.4

## [1.3.3] - 2021-09-22
### Fixed
- removed action with not existing function for bulk status change to complete
- added POST fields check before values save

### Updated
- itella-api library to v2.3.3

### Improved
- added a ability to add comment to label

## [1.3.2] - 2021-07-13
### Fixed
- changed file_get_contents() PHP function to equivalent Wordpress function
- cart amount total calculation
- fixed min value for price table first input field

### Improved
- added a ability to set shipping method description

## [1.3.1] - 2021-06-29
### Improved
- added a ability to set shipping price by cart amount
- created tracking code url set by order country

## [1.3.0] - 2021-05-13
### Fixed
- added hidden fields to cart page
- customized map marker jumping animation to avoid conflicts
- removed "important" from hidden class
- fixed notice message in shipping class function

### Improved
- added a ability to specify the courier email address
- improved to use billing_country if shipping_country not exist
- created detailed error display when the label cannot be generated

## [1.2.7] - 2021-02-19
### Fixed
- fixed shipping methods showing in cart/checkout

### Changed
- changed country code input field to select field

## [1.2.6.1] - 2021-02-17
### Fixed
- fixed shipping price calculation

## [1.2.6] - 2021-01-22
### Updated
- itella-api library to v2.3.1

### Changed
- applied changes by the updated library

## [1.2.5] - 2021-01-20
### Improved
- added a ability set shipping price for specific shipping class

### Updated
- itella-api library to v2.3.0

## [1.2.4] - 2021-01-12
### Changed
- changed name "Itella" to "Smartpost"

### Updated
- updated all translations

## [1.2.3] - 2020-12-23
### Fixed
- fixed js error on checkout page
- fixed shipping method settings display by checkbox

### Improved
- added "Settings" link for Itella plugin in plugins list
- added more information to plugin description

## [1.2.2] - 2020-11-20
### Fixed
- fixed plugin deletion error

### Changed
- changed button "Register shipment" behavior via ajax in "Itella shipments" admin page

### Improved
- added the ability to register shipments via bulk action
- added pickup point selection field display, when shipping method selection style is dropdown
- added the ability to display the shipping price according to the weight of the cart

### Added
- added Latvian frontend translation
- added Estonian frontend translation
- added Russian frontend translation

## [1.2.1] - 2020-11-18
### Fixed
- fixed notice error in Woocommerce settings pages

### Updated
- itella-api library to v2.2.5

## [1.2.0] - 2020-10-30
### Fixed
- fixed scripts loading only in their designated locations

### Added
- created tracking number display in order and emails
- created option to select the appearance of pickup select field in checkout page
- created the ability to choose how many posts will be displayed per page in manifest generation page
- created the ability to generate a manifest for all orders in the current tab
- added Finnish translation

### Updated
- itella-mapping.js to v1.3.1

## [1.1.9] - 2020-10-08
### Fixed
- fixed "Enable" checkmark to be more consistent
- fixed pickup point selection when paypal checkout is used
- fixed missing API libraries from release package

### Improved
- now price settings are more robust
- improved code quality in some parts

## [1.1.8] - 2020-09-09
### Changed
- changed string in order when adding shipping details to order. Carrier -> Shipping method

## [1.1.7] - 2020-09-09
### Updated
- itella-mapping.js to v1.2.3
