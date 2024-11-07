<?php
// TODO: write docs
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use Mijora\Itella\Pdf\Manifest;

$items = array(
  array(
    'track_num' => 'JJFItestnr00000000015',
    'weight' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000016',
    'weight' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000017',
    'weight' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
  array(
    'track_num' => 'JJFItestnr00000000018',
    'weight' => 1,
    'delivery_address' => 'Test Tester, Example str. 6, 44320 City, LT',
  ),
);

$translation = array(
  'sender_address' => 'Sender address:',
  'nr' => 'No.',
  'track_num' => 'Tracking number',
  'date' => 'Date',
  'amount' => 'Quantity',
  'weight' => 'Weight (kg)',
  'delivery_address' => 'Delivery address',
  'courier' => 'Courier',
  'sender' => 'Sender',
  'name_lastname_signature' => 'name, surname, signature',
);

$manifest = new Manifest();
$manifest
  ->setStrings($translation)
  ->setSenderName('TEST Web Shop')
  ->setSenderAddress('Shop str. 150')
  ->setSenderPostCode('47174')
  ->setSenderCity('Kaunas')
  ->setSenderCountry('LT')
  ->addItem($items)
  ->printManifest('manifest.pdf');
