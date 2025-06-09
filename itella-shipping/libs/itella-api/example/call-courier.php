<?php
// TODO: TBD. Debends on pakettikauppa
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use Mijora\Itella\CallCourier;
use Mijora\Itella\ItellaException;
use Mijora\Itella\Pdf\Manifest;

/**
 * DEMO MANIFEST TO BE ATTACHED
 */
$items = array(
  array(
    'track_num' => 'JJFItestnr00000000015',
    'weight' => 1,
    'amount' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000016',
    'weight' => 1.5,
    'amount' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000017',
    'weight' => 0.75,
    'amount' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000018',
    'weight' => 1.25,
    'amount' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
);

$manifest = new Manifest();
$manifest_string = $manifest
  ->setSenderName('TEST Web Shop')
  ->setSenderAddress('Shop str. 150')
  ->setSenderPostCode('47174')
  ->setSenderCity('Kaunas')
  ->setSenderCountry('LT')
  ->addItem($items)
  ->setToString(true)
  ->setBase64(true)
  ->printManifest('manifest.pdf')
;


try {
  $caller = new CallCourier($email);
  $result = $caller
    ->setUsername($p_user)
    ->setPassword($p_secret)
    ->setSenderEmail('shop@shop.lt')
    ->setSubject('E-com order booking')
    ->setPickUpAddress(array(
      'sender' => 'Name / Company name',
      'address_1' => 'Street 1',
      'postcode' => '12345',
      'city' => 'City',
      'country' => 'LT',
      'pickup_time' => '8:00 - 17:00', // Optional if using setPickUpParams() function
      'contact_phone' => '+37060000000',
    ))
    ->setPickUpParams(array(
      'date' => '2001-12-20',
      'time_from' => '08:00',
      'time_to' => '17:00',
      'info_general' => 'Message to courier',
      'id_sender' => '123', // Company code or VAT code
    ))
    ->setAttachment($manifest_string, true)
    ->setItems($items)
    ->showMessagesPrefix(true) // specify if a prefix (e.g. name of the call method) should be displayed at the beginning of returned messages
    ->callCourier()
  ;
  
  if (!empty($result['errors'])) {
    echo '<b>Errors:</b><br/>';
    echo implode('<br/>', $result['errors']);
    echo '<br/><br/>';
  }
  if (!empty($result['success'])) {
    echo '<b>Success:</b><br/>';
    echo implode('<br/>', $result['success']);
  }
} catch (ItellaException $e) {
  echo 'Failed to call courier, reason: ' . $e->getMessage();
}
