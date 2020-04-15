<?php
// TODO: TBD. Debends on pakettikauppa
require '../vendor/autoload.php';

use \Mijora\Itella\CallCourier;

if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

$sendTo = $email;
try {
  $caller = new \Mijora\Itella\CallCourier($sendTo, true);
  $result = $caller
    ->setSenderEmail('shop@shop.lt')
    ->setPickUpAddress(array(
      'sender' => 'Name / Company name',
      'address' => 'Street, Postcode City, Country',
      'pickup_time' => '8:00 - 17:00',
      'contact_phone' => '865465411',
    ))
    ->buildMailBody();
    //->callCourier();
  if ($result) {
    echo 'Email sent to: <br>' . $result;
  }
} catch (\Exception $e) {
  echo 'Failed to send email, reason: ' . $e->getMessage();
}
