<?php
// TODO: no longer used, examples split into its own php files
// require '../vendor/autoload.php';

// use Mijora\Itella\Locations\PickupPoints;
// use Mijora\Itella\Shipment\GoodsItem;
// use Mijora\Itella\Shipment\Party;


// /**
//  * PickupPoints Tests
//  */
// // $start = microtime(true);
// // $itellaPickupPointsObj = new PickupPoints('https://locationservice.posti.com/api/2/location');
// // $itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('lt');
// // $itellaPickupPointsObj->saveLocationsToJSONFile('../temp/test.json', json_encode($itellaLoc));
// // echo "Done. Runtime: " .  (microtime(true) - $start) . 's';
// // echo json_encode($itellaLoc);
// // die;

// /**
//  * Shipment Tests
//  */

// // env.php contains $user, $pass, $contract variables needed in these examples
// if (!file_exists('env.php')) {
//   copy('sample.env.php', 'env.php');
// }
// require('env.php');

// $isTest = true;

// // $current_token = [];

// // $token_file = '../temp/token.json';

// // if (file_exists($token_file)) {
// //   echo 'Saved token found<br>';
// //   $current_token = json_decode(file_get_contents($token_file), true);
// // }

// // $auth = new \Mijora\Itella\Auth($user, $pass, $isTest);

// /**
//  * returns authorization array
//  * [
//  *  access_token  =>  string  // token string
//  *  token_type    =>  string  // bearer
//  *  expires_in    =>  number  // seconds untill token is invalid
//  *  expires       =>  number  // unix timestamp when token becomes invalid
//  * ]
//  */
// // if (count($current_token) < 1 || $current_token['expires'] <= time()) {
// //   echo 'Getging new Token<br>';
// //   $authObj = $auth->getAuth();
// //   file_put_contents('token.json', json_encode($authObj));
// // } else {
// //   echo 'Using saved Token<br>';
// //   $auth->setTokenArr($current_token);
// // }

// $sender = new \Mijora\Itella\Shipment\Party(Party::ROLE_SENDER);
// $sender
//   ->setContract($contract) // important comes from supplied tracking code interval
//   ->setName1('TEST Web Shop')
//   ->setStreet1('Raudondvario pl. 150')
//   ->setPostCode('47174')
//   ->setCity('Kaunas')
//   ->setCountryCode('LT')
//   ->setContactMobile('865454321');

// //echo $sender->getXML()->asXML();

// $receiver = new \Mijora\Itella\Shipment\Party(Party::ROLE_RECEIVER);
// $receiver
//   ->setName1('Testas Testutis')
//   ->setName2("c/o Banginis, Pramones pr. 6B")
//   ->setStreet1("Pramones pr. 6")
//   ->setPostCode("51267")
//   ->setCity("KAUNAS")
//   ->setCountryCode('LT')
//   ->setContactName('Rimvydas')
//   ->setContactMobile('865412345')
//   ->setContactEmail('testas@testutis.lt');

// //echo $receiver->getXML()->asXML();

// // Pickup points has no extra services (except 3201 that marks it as pickup and thats assigned by default)
// $item = new \Mijora\Itella\Shipment\GoodsItem(GoodsItem::PRODUCT_PICKUP);
// $item
//   ->setTrackingNumber('JJFItestnr00000000015') // mandatory
//   ->setGrossWeight(2) // kg, optional
//   ->setVolume(0.1); // m3, optional

// // Example of courier product
// /**
//  * Extra service:
//  * 3101 - COD - if set, Shipment must have COD information set
//  * 3102 - Multiparcel - Shipment counts GoodsItems with this set and fills in required PackageQuantity
//  * 3104 - Fragile
//  * 3166 - Call before Delivery
//  * 3174 - Oversized
//  */
// $item2 = new \Mijora\Itella\Shipment\GoodsItem(GoodsItem::PRODUCT_COURIER);
// $item2
//   ->setTrackingNumber('JJFItestnr00000000016') // mandatory
//   ->addExtraService([3101, 3102, 3104]) // can have multiple extra services (either in array or as single) 
//   ->setGrossWeight(2) // kg, optional
//   ->setVolume(0.1); // m3, optional
// $item3 = new \Mijora\Itella\Shipment\GoodsItem(GoodsItem::PRODUCT_COURIER);
// $item3
//   ->setTrackingNumber('JJFItestnr00000000017') // mandatory
//   ->addExtraService([3101, 3102, 3104]) // can have multiple extra services (either in array or as single) 
//   //->setGrossWeight(2) // kg, optional
//   //->setVolume(0.1) // m3, optional
// ;

// //echo $item->getXML()->asXML();

// $shipment = new \Mijora\Itella\Shipment\Shipment($p_user, $p_secret, $isTest);
// $shipment
//   //->setAuth($auth) // Authentication class object
//   //->setSenderId($user) // senderId we use API username
//   //->setReceiverId('ITELLT') // could be ITELLT, ITELLV, ITELEE - waiting for details how to decide which one
//   ->setShipmentNumber('TESTNUMBER') // shipment number 
//   ->setShipmentDateTime(date('c')) // when package will be ready (just use current time)
//   ->setSenderParty($sender) // Sender class object
//   ->setReceiverParty($receiver) // Receiver class object
//   ->addGoodsItems([$item2, $item3]) // GoodsItem class object (or in case of multiparcel can be array of GoodsItem)
//   // bellow is COD information required when GoodsItem has COD extra service set
//   ->setBIC('testBIC')
//   ->setIBAN('LT123425678')
//   ->setValue(100.50)
//   ->setReference($shipment->generateCODReference('012'));

// // Label tests
// // $label = new \Mijora\Itella\Pdf\Label($shipment);
// // $done = $label->printLabel('sample.pdf', dirname(__FILE__) . '/../temp/');
// // if ($done) {
// //   echo '<br>PDF Saved to file';
// // }

// //To get Shipment Document creation time and Sequence (used to identify requests)
// $documentDateTime = $shipment->getDocumentDateTime();
// $sequence = $shipment->getSequence();

// //$result = $shipment->sendShipment(); 
// $xml = false;
// $result['success'] = $shipment->asXML(); $xml = true;
// if (isset($result['error'])) {
//   echo '<br>Shipment Failed with error: ' . $result['error'];
// } else {
//   if ($xml) {
//     echo "<br>XML CODE RETURN<br>";
//   }
//   echo '<br>Shipment sent: <code>' . $result['success'] . '</code>';
//   file_put_contents(dirname(__FILE__) . '/../temp/registered_tracks.log', "\n" . $result['success'], FILE_APPEND);
// }

// echo '<br>Done';

// echo $shipment->getXML()->asXML();
// Debuging request and response
// $transfer_log_file = '../temp/transfer.log';
// file_put_contents($transfer_log_file, '=======================', FILE_APPEND);
// file_put_contents($transfer_log_file, $shipment->getXML()->asXML(), FILE_APPEND);
// file_put_contents($transfer_log_file, '==== Response ====', FILE_APPEND);
// file_put_contents($transfer_log_file, $response, FILE_APPEND);
// file_put_contents($transfer_log_file, '=======================', FILE_APPEND);
