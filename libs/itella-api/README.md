# Itella-API v2.0.0

Its a wrapper library for Pakettikauppa API library.

## Using Itella-API library
- `__PATH_TO_LIB__` is path to where itella-api is placed. This will load Mijora\Itella namespace
```php
require __PATH_TO_LIB__ . 'itella-api/vendor/autoload.php';
```

Validations, checks, etc. throws ItellaException and calls to library classes should be wrapped in: blocks 
```php
try {
  // ...
} catch (ItellaException $e) {
  // ...
}
```

Any function starting with `add` or `set` returns its class so functions can be chained.

## Authentication
---
Uses supplied `user` and `secret`. It is called during Shipment creation.


## Creating Sender
---
`Party::ROLE_SENDER` is used to identify sender.

Minimum required setup:
- Phone number must be in international format, library will try to fix given number or throw ItellaException.
```php
use Mijora\Itella\Shipment\Party;
use Mijora\Itella\ItellaException;

try {
  $sender = new Party(Party::ROLE_SENDER);
  $sender
    // ->setContract('000000')            // currently not used
    ->setName1('TEST Web Shop')           // sender name
    ->setStreet1('Test str. 150')         // sender address
    ->setPostCode('47174')                // sender post code
    ->setCity('Kaunas')                   // sender city
    ->setCountryCode('LT')                // sender country code in ISO 3166-1 alpha-2 format (two letter code)
    ->setContactMobile('+37061234567')    // sender phone number in international format
    ->setContactEmail('sender@test.lt');  // sender email
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```


## Creating Receiver
---
`Party::ROLE_RECEIVER` is used to identify sender.

Minimum required setup:
- Phone number must be in international format, library will try to fix given number or throw ItellaException.
```php
use Mijora\Itella\Shipment\Party;
use Mijora\Itella\ItellaException;

try {
  $receiver = new Party(Party::ROLE_RECEIVER);
  $receiver
    ->setName1('Testas')                    // receiver name
    ->setStreet1('None str. 4')             // receiver address
    ->setPostCode('47174')                  // receiver post code
    ->setCity('Kaunas')                     // receiver city
    ->setCountryCode('LT')                  // receiver country code in ISO 3166-1 alpha-2 format (two letter code)
    ->setContactMobile('+37067654321')      // receiver phone number in international format
    ->setContactEmail('receiver@test.lt');  // receiver email
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```


## Creating Order Items
---
- If using multiparcel additional service simply create multiple GoodsItem and register them to Shipment.
```php
use Mijora\Itella\Shipment\GoodsItem;
use Mijora\Itella\ItellaException;

try {
  $item = new GoodsItem();
  $item
    ->setGrossWeight(0.5)       // kg, optional
    ->setVolume(0.5)            // m3, optional
    ->setContentDesc('Stuff');  // optional package content description
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

## Creating Additional Services
---
**Shipment::PRODUCT_COURIER** available additional services:
- Must be set manualy
  - 3101 - Cash On Delivery (only by credit card). 
    **Requires** array with this information:
      - `amount`    => amount to be payed in EUR,
      - `account`   => bank account (IBAN),
      - `codbic`    => bank BIC, 
      - `reference` => COD Reference, can be used `Helper::generateCODReference($id)` where `$id` can be Order ID.
  - 3104 - Fragile
  - 3166 - Call before Delivery
  - 3174 - Oversized
- Will be set automatically
  - 3102 - Multi Parcel, will be set automatically if Shipment has more than 1 and up to 10 GoodsItem.
    **Requires** array with this information:
      - `count` => Total of registered GoodsItem.


**Shipment::PRODUCT_PICKUP** available additional services:
- Will be set automatically
  - 3201 - Pickup Point, is set automatically when pick up point ID (pupCode from Locations API) is registered into Shipment.
    **Requires** array with this information:
      - `pickup_point_id` => Pickup point pupCode.

Trying to set additional service that is not available for set product code will throw ItellaException.

Creating additional service that does not need extra information (eg. with Fragile):
```php
use Mijora\Itella\Shipment\AdditionalService;
use Mijora\Itella\ItellaException;

try {
  $service_fragile = new AdditionalService(AdditionalService::FRAGILE);
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

Creating COD additional service:
```php
use Mijora\Itella\Shipment\AdditionalService;
use Mijora\Itella\Helper;
use Mijora\Itella\ItellaException;

try {
  $service_cod = new AdditionalService(
    AdditionalService::COD,
    array(
      'amount'    => 100,
      'codbic'    => 'XBC0101',
      'account'   => 'LT100000000000',
      'reference' => Helper::generateCODReference('666')
    )
  );
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```


## Create Shipment
---
Available product codes:
* Shipment::PRODUCT_COURIER = 2317
* Shipment::PRODUCT_PICKUP  = 2711

Shipment can be either one, but never both. See Additional Services for what services is available to each product code.

**Shipment product code should always be set first.**

When registering GoodsItem its possible to register one at a time using
`$shipment->addGoodsItem(GoodsItem)`
or multiple passing them in array to
`$shipment->addGoodsItems(array(GoodsItem, GoodsItem))`

When registering AdditionalService its possible to register one at a time using
`$shipment->addAdditionalService(AdditionalService)`
or multiple passing them in array to
`$shipment->addAdditionalServices(array(AdditionalService, AdditionalService))`


Courier Shipment example (uses variables from above examples):
```php
use Mijora\Itella\Shipment\Shipment;
use Mijora\Itella\ItellaException;

try {
  $shipment = new Shipment($p_user, $p_secret);
  $shipment
    ->setProductCode(Shipment::PRODUCT_COURIER) // product code, should always be set first
    ->setShipmentNumber('Test_ORDER')           // shipment number, Order ID is good here
    ->setSenderParty($sender)                   // Register Sender
    ->setReceiverParty($receiver)               // Register Receiver
    ->addAdditionalServices(                    // Register additional services
      array($service_fragile, $service_cod)
    )
    ->addGoodsItems(                            // Register GoodsItem
      array($item)
    )
  ;
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

Pickup point Shipment example (uses variables from above examples):
```php
use Mijora\Itella\Shipment\Shipment;
use Mijora\Itella\ItellaException;

$user = 'API_USER';     // API user
$secret = 'API_SECRET'; // API secret / password

try {
  $shipment = new Shipment($user, $secret);
  $shipment
    ->setProductCode(Shipment::PRODUCT_PICKUP)  // product code, should always be set first
    ->setShipmentNumber('Test_ORDER')           // shipment number, Order ID is good here
    ->setSenderParty($sender)                   // Register Sender
    ->setReceiverParty($receiver)               // Register Receiver
    ->setPickupPoint('071503201')               // Register pickup point pupCode
    ->addGoodsItem($item)                       // Register GoodsItem
  ;
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

Once all information is supplied - shipment can be registered.
If registration is successfull, tracking number will be returned.
In this example returned tracking number is displayed, normaly it would be saved to order for later use to request shipment label PDF.
```php
try {
  $tracking_number = $shipment->sendShipment();
  echo "Shipment registered:\n <code>" . $tracking_number . "</code>\n";
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

If there is need to check request XML it can be done using `asXML()`
```php
try {
  $xml = $shipment->asXML();
  file_put_contents('request.xml', $xml);
} catch (ItellaException $e) {
  // Handle validation exceptions here
}
```

## Printing Label
---
It is advised to always download label when it is needed. For that Shipment class is used.
result will be base64 encoded pdf file. If multiple tracking numbers (in array) is passed pdf will contain all those labels.
```php
use Mijora\Itella\Shipment\Shipment;
use Mijora\Itella\ItellaException;

$user = 'API_USER';     // API user
$secret = 'API_SECRET'; // API secret / password

$track = 'JJFI12345600000000001';
// or if need multiple in one pdf
// $track = ['JJFI12345600000000001', 'JJFI12345600000000010'];

try {
  $shipment = new Shipment($user, $secret);
  $pdf_base64 = $shipment->downloadLabels($track);
  $pdf = base64_decode($pdf_base64);
  if ($pdf) { // check if its not empty
    if (is_array($track)) {
      $track = 'labels';
    }
    $path = $track . '.pdf';
    $is_saved = file_put_contents($path, $pdf);
    $filename = 'labels.pdf';
    if (!$is_saved) { // make sure it was saved
      throw new ItellaException("Failed to save label pdf to: " . $path);
    }

    // make sure there is nothing before headers
    if (ob_get_level()) ob_end_clean();
    header("Content-Type: application/pdf; name=\"{$filename}\"");
    header("Content-Transfer-Encoding: binary");
    // disable caching on client and proxies, if the download content vary
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    readfile($path);
  } else {
    throw new ItellaException("Downloaded label data is empty.");
  }
} catch (ItellaException $e) {
  echo "Exception: <br>\n" . $e->getMessage() . "<br>\n";
}
```
Above example checks that response isnt empty (if tracking number is wrong it still returns empty response), saves to file and loads into browser.


## Locations API
---
When using Pickup Point option it is important to have correct list of pickup points. Also when creating Shipment to send to pickup point it will require that pickup point ID.
```php
use Mijora\Itella\Locations\PickupPoints;

$pickup = new PickupPoints('https://locationservice.posti.com/api/2/location');
// it is advised to download locations for each country separately
// this will return filtered pickup points list as array
$itella_loc = $pickup->getLocationsByCountry('LT');
// now points can be stored into file or database for future use
$pickup->saveLocationsToJSONFile('itella_locations_lt.json', json_encode($itella_oc));
```
