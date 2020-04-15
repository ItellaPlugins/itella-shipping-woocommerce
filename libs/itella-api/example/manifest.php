<?php
// TODO: write docs
require '../vendor/autoload.php';

use Mijora\Itella\Locations\PickupPoints;
use Mijora\Itella\Shipment\GoodsItem;
use Mijora\Itella\Shipment\Party;

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

$translation = array(
  'sender_address' => 'Siuntėjo adresas:',
  'nr' => 'Nr.',
  'track_num' => 'Siuntos numeris',
  'date' => 'Data',
  'amount' => 'Kiekis',
  'weight' => 'Svoris (kg)',
  'delivery_address' => 'Pristatymo adresas',
  'courier' => 'Kurjerio',
  'sender' => 'Siuntėjo',
  'name_lastname_signature' => 'vardas, pavardė, parašas',
);

$manifest = new \Mijora\Itella\Pdf\Manifest();
$manifest
  ->setStrings($translation)
  ->setSenderName('TEST Web Shop')
  ->setSenderAddress('Raudondvario pl. 150')
  ->setSenderPostCode('47174')
  ->setSenderCity('Kaunas')
  ->setSenderCountry('LT')
  ->addItem($items)
  ->printManifest('manifest.pdf');
