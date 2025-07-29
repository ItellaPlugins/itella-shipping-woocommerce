# Changelog

## [2.4.7]
### Fixed
- fixed that in the courier invitation via Postra, the shipments numbers would be written under the Instructions parameter

## [2.4.6]
### Fixed
- fixed to display an error message when a timeout occurs while trying to get a Access Token
- fixed to display an error message when an Exception occurs in Pakettikauppa library while trying to register a shipment

## [2.4.5]
### Added
- added the ability to add fixes to used third-party libraries (it uses the patching library cweagans/composer-patches)
- added a patch for the SimpleXMLElement class of the Pakettikauppa library

## [2.4.4]
### Fixed
- fixed prefix control
- fixed pickup time automatic generation

### Added
- added `enableMethod()` and `disableMethod()` functions to make it easier to manage methods

## [2.4.3]
### Fixed
- fixed a bug in the POSTRA method where user logins are passed through separate functions, instead of directly to the calling function

### Added
- added a message about invalid logins when the courier is called via POSTRA

### Changed
- removed the ability to specify logins in a courier invitation by passing them directly to the invitation function. The `setUsername()` and `setPassword()` functions must be used.
- changed that when calling a courier in LT and LV, the API method will not be used

## [2.4.2]
### Fixed
- fixed POSTRA query error

### Added
- added phone number to POSTRA request

## [2.4.1]
### Fixed
- fixed the courier invitation Email method not trying to add new header if headers have already been sent

### Added
- new functions setUsername() and setPassword() added to the CallCourier class

### Changed
- The courier invitation POSTRA method is used only if the country indicated in the sender's address is LT or LV.

## [2.4.0]
### Fixed
- fixed disablePhoneCheck() and enablePhoneCheck() functions in Shipment\Party class

### Added
- added the ability to call a courier via the POSTRA system
- added a separate Client class

### Changed
- unified response for all courier call methods
- changed so that instead of a simple courier call result, an array with errors and success messages would be returned

## [2.3.9]
### Fixed
- fixed deprecated function message when generating manifest

### Changed
- changed logo to Smartposti

## [2.3.8.1] - Fix last update bug
### Fixed
- fixed courier call bug after last update

## [2.3.8] - Use Locations API v3
### Removed
- removed the Auth class as it is no longer used

### Changed
- moved location API url from class Shipment to class PickupPoints
- changed locations API url to v3

### Added
- added request error return
- added support for Lithuanian phone number prefix "0"

## [2.3.7] - Logo & PDF lib
### Updated
- updated logo used for manifest
- tecnickcom/tcpdf updated to 6.5.0

## [2.3.6] - Dependencies update
### Updated
- pakettikauppa/api-library to v2.0.6
- added support for $size variable in downloadLabels

## [2.3.5] - Dependencies update
### Updated
- Updated dependencies, to support PHP 8

## [2.3.4] - COD Service for Pickup Point type of shipment
### Added
- For product (2711) it is now allowed to set COD additional service (3101)

## [2.3.3] - FI pickupoints
### Changed
- PickupPoints for country FI wont filter out type PICKUPPOINT

### Added
- Function to disable phone fixing on Party class. Functions `disablePhoneCheck` and `enablePhoneCheck`.
**NOTE:** Disabling phone checking still checks that phone format matches international.

### Updated
- setasign/fpdi updated to v2.3.6

## [2.3.2] - Label comment
### Added
- Added `setComment()` function to Shipment class. This sets a comment that will be displayed on label

## [2.3.1] - Address splitting
### Changed
- divided sender address into different parameters
- added a ability to translate email text

## [2.3.0] - ItellaPickups API
### Added
- created ItellaPickups API
- added a ability to call courier via API

## [2.2.5] - Receiver info change for Pickups
### Changed
- Receiver information is altered if pickup point pupCode is set. Using set pupCode location information will be set as street1, city and postalCode instead of original receiver street1, city and postalCode

## [2.2.4] - Finland mobile numbers
### Improved
- Improved Finland mobile number parsing

## [2.2.3] - Manifest date format
### Added
- Optional dateFormat argument to Manifest constructor for setting custom manifest generation date format. Default format `'Y-m-d'` (2020-12-30)

## [2.2.2] - Auth update
### Updated
- Posti authentication url for pakeetikauppa API
- Updated dependencies
- Cleaned up some unused code

## [2.2.1] - 2020-05-21
### Added
- Finland mobile number validation
- Finland mobile number fix (adds country calling code if its missing)
- This changelog file

## [2.0.0] - Rework to use Pakettikaupa-API
### Added
- Pakettikaupa-API library for Shipment registration, Label generation

## [1.0.0] - Initial release