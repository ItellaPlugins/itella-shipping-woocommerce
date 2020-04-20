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
    'delivery_address' => 'Testas Testutis, Pramones pr. 6, 51267 Kaunas, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000016',
    'weight' => 1,
    'delivery_address' => 'Testas Testutis, Pramones pr. 6, 51267 Kaunas, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000017',
    'weight' => 1,
    'delivery_address' => 'Testas Testutis, Pramones pr. 6, 51267 Kaunas, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000018',
    'weight' => 1,
    'delivery_address' => 'Testas Testutis, Pramones pr. 6, 51267 Kaunas, LT',
  ),
);

$manifest = new Manifest();
$manifest_string = $manifest
  ->setSenderName('TEST Web Shop')
  ->setSenderAddress('Raudondvario pl. 150')
  ->setSenderPostCode('47174')
  ->setSenderCity('Kaunas')
  ->setSenderCountry('LT')
  ->addItem($items)
  ->setToString(true)
  ->setBase64(true)
  ->printManifest('manifest.pdf')
;


$sendTo = $email;
try {
  $caller = new CallCourier($sendTo);
  $result = $caller
    ->setSenderEmail('shop@shop.lt')
    ->setSubject('E-com order booking')
    ->setPickUpAddress(array(
      'sender' => 'Name / Company name',
      'address' => 'Street, Postcode City, Country',
      'pickup_time' => '8:00 - 17:00',
      'contact_phone' => '865465412',
    ))
    ->setAttachment($manifest_string, true)
    //->buildMailBody()
    ->callCourier()
  ;
  if ($result) {
    echo 'Email sent to: <br>' . $email;
  }
} catch (ItellaException $e) {
  echo 'Failed to send email, reason: ' . $e->getMessage();
}
