<?php
// TODO: write docs
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use \Mijora\Itella\Shipment\Party;
use \Mijora\Itella\Shipment\GoodsItem;
use \Mijora\Itella\Shipment\AdditionalService;
use \Mijora\Itella\Shipment\Shipment;
use \Mijora\Itella\Helper;
use Mijora\Itella\ItellaException;

try {

  // Create and configure sender
  $sender = new Party(Party::ROLE_SENDER);
  $sender
    ->setContract(${'contract_' . Shipment::PRODUCT_EXPRESS_BUSINESS_DAY}) // important comes from supplied tracking code interval
    ->setName1('TEST Web Shop')
    ->setStreet1('Shop str. 150')
    ->setPostCode('47174')
    ->setCity('Kaunas')
    ->setCountryCode('LT')
    ->setContactMobile('+37060000000')
    ->setContactEmail('sender@test.lt');

  // Create and configure receiver
  $receiver = new Party(Party::ROLE_RECEIVER);
  $receiver
    ->setName1('Mike Test')
    //->setName2("c/o Banginis, Pramones pr. 6B")
    ->setStreet1("latvia str. 6")
    ->setPostCode("LV-0011")
    ->setCity("riga")
    ->setCountryCode('lv')
    //->setContactName('Mike')
    ->setContactMobile('25841345')
    ->setContactEmail('receiver@test.lv')
    ;

  // Create GoodsItem (parcel)
  $item = new GoodsItem();
  $item
    ->setGrossWeight(2) // kg
    ->setVolume(0.1); // m3
  $item2 = new GoodsItem();
  $item2
    ->setGrossWeight(0.5) // kg
    ->setVolume(0.5); // m3

  // Create additional services
  $service_cod = new AdditionalService(3101, array(
    'amount' => 100,
    'account' => 'LT100000000000',
    'reference' => Helper::generateCODReference('666'),
    'codbic' => 'XBC0101'
  ));

  $service_fragile = new AdditionalService(3104);

  // Create shipment object
  $shipment = new Shipment($p_user, $p_secret);
  $shipment
    ->setProductCode(Shipment::PRODUCT_EXPRESS_BUSINESS_DAY) // should always be set first
    ->setShipmentNumber('Test_ORDER_fragile') // shipment number 
    //->setShipmentDateTime(date('c')) // when package will be ready (just use current time)
    ->setSenderParty($sender) // Sender class object
    ->setReceiverParty($receiver) // Receiver class object
    ->addAdditionalServices([$service_fragile, $service_cod]) // set additional services
    ->addGoodsItems([$item, $item2])
    ->setComment('Comment for courier label')
  ;

  $xml = false;

  if ($xml) {
    $result = $shipment->asXML();
    echo "<br>XML REQUEST<br>\n";
    echo "<br>Shipment sent:<br>\n<code>" . $result . "</code>\n";
  } else {
    $result = $shipment->registerShipment();
    echo "<br>Shipment registered:<br>\n<code>" . $result . "</code>\n";
    file_put_contents(dirname(__FILE__) . '/../temp/registered_tracks.log', "\n" . $result, FILE_APPEND);
  }
} catch (ItellaException $e) {
  echo "\n<br>Exception:<br>\n"
    . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n"
    . str_replace("\n", "<br>\n", $e->getTraceAsString());
}
echo '<br>Done';
