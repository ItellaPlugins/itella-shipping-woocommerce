<?php
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use Mijora\Itella\Locations\PickupPoints;

/**
 * PickupPoints Tests
 */
$start = microtime(true);
$itellaPickupPointsObj = new PickupPoints('https://locationservice.posti.com/api/2/location');
$itellaLoc = $itellaPickupPointsObj->getLocationsByCountry('lt');
$itellaPickupPointsObj->saveLocationsToJSONFile('../temp/test.json', json_encode($itellaLoc));
echo "Done. Runtime: " .  (microtime(true) - $start) . 's';
echo json_encode($itellaLoc);
