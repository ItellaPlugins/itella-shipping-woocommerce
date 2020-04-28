<?php
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);

require '../vendor/autoload.php';

use Pakettikauppa\Client;

$client = new Client(array(
  'foo' => array(
    'api_key' => $p_user,
    'secret' => $p_secret,
    'base_uri' => 'https://nextshipping.posti.fi',
    'use_posti_auth' => true,
    'posti_auth_url' => 'https://oauth.posti.com',
   ),
  ), 'foo'
 );
 // get token from cache
 // if token is not in cache, then:
 $token = $client->getToken();
 // save token to cache
 $client->setAccessToken($token->access_token);



use Pakettikauppa\Shipment;
use Pakettikauppa\Shipment\Sender;
use Pakettikauppa\Shipment\Receiver;
use Pakettikauppa\Shipment\AdditionalService;
use Pakettikauppa\Shipment\Info;
use Pakettikauppa\Shipment\Parcel;

//use Pakettikauppa\Client;

$sender = new Sender();

$sender->setName1('Stuff from the internet Ltd');
$sender->setAddr1('Somestreet 123');
$sender->setPostcode('14120');
$sender->setCity('Tampere');
$sender->setCountry('LT');

$receiver = new Receiver();
$receiver->setName1('John Doe');
//$receiver->setName2('c/o Rimi Pulko, Pulko g. 51A, Alytus');
$receiver->setAddr1('APulko g. 51');
$receiver->setPostcode('60135');
$receiver->setCity('ALYTUS');
$receiver->setCountry('LT');
$receiver->setEmail('john@doe.com');
$receiver->setPhone('358 123 4567890');

$info = new Info();
$info->setReference('12344');

$additional_service = new AdditionalService();
$additional_service->setServiceCode(/* 3201 */3104); // fragile
//$additional_service->setServiceCode(3101); // fragile


$parcel = new Parcel();
$parcel->setReference('1234456');
$parcel->setWeight(1.5); // kg
$parcel->setVolume(0.001); // m3
$parcel->setContents('Stuff and thingies');


$shipment = new Shipment();
$shipment->setShippingMethod(2317); // shipping_method_code that you can get by using listShippingMethods()
$shipment->setSender($sender);
$shipment->setReceiver($receiver);
$shipment->setShipmentInfo($info);
$shipment->addParcel($parcel);
$shipment->addAdditionalService($additional_service);

$cod = new AdditionalService();
$cod->setServiceCode(3101);
$cod->addSpecifier('amount', 100);
$cod->addSpecifier('account', 'LT1231654621');
$cod->addSpecifier('reference', '1032');
$cod->addSpecifier('codbic', 'XBCC100');
$shipment->addAdditionalService($cod);


//$shipment->setPickupPoint('16443');
file_put_contents('../temp/xml.log', $shipment->asXml()); 
//echo 'done'; die;

try {
   $client->createTrackingCode($shipment);
   $track = $shipment->getTrackingCode();

    // if ($client->createTrackingCode($shipment)) {
    //     if($client->fetchShippingLabel($shipment))
    //         file_put_contents('../temp/' . $shipment->getTrackingCode() . '.pdf', base64_decode($shipment->getPdf()));
    // }
    $base = $client->fetchShippingLabels([$track]);
    //echo base64_decode($base->{'response.file'});
    file_put_contents('../temp/'.$track.'.pdf', base64_decode($base->{'response.file'}));
    echo $track;
    // $result = $client->listShippingMethods();
    // echo json_encode($result);
} catch (\Exception $ex)  {
    echo $ex->getMessage();
}