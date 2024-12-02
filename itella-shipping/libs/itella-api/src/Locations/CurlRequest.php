<?php
// TODO: write docs
namespace Mijora\Itella\Locations;

class CurlRequest
{
  public static function doCurlRequest($url)
  {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error_no = curl_errno($curl);
    $error_msg = ($error_no) ? curl_error($curl) : '';

    curl_close($curl);

    if ($http_code != 200) {
      return array('error' => 'Request returned an error with HTTP code ' . $http_code . ': ' . $error_msg);
    }

    // response is a nested array
    $response = json_decode($response, true);

    if (empty($response['locations'])) {
      return array('error' => 'Empty response for url ' . $url);
    }

    return $response;
  }

  public static function getLocations($result, $lang = null)
  {
    $locations = array();

    // check if result contains error
    if (isset($result['error'])) {
      return $result; // return array with error
    }

    foreach ($result['locations'] as $loc) {
      $outputObj = new Output($loc, $lang);
      $type = strtoupper($outputObj->getType());
      $partner = strtoupper($outputObj->getPartnerType());
      $countryCode = $outputObj->getCountryCode();
      // Filter just Itella supported locations
      if (($type != 'PICKUPPOINT' && $type != 'SMARTPOST') 
        || ($type == 'SMARTPOST' && $partner == 'POSTI')
        || ($type == 'PICKUPPOINT' && strtoupper($countryCode) == 'FI')
      ) {
        $locations[] = array(
          "id" => $outputObj->getId(),
          "type" => $outputObj->getType(),
          "postalCode" => $outputObj->getPostalCode(),
          "address" => $outputObj->getAddress(),
          "publicName" => $outputObj->getPublicName(),
          'locationName' => $outputObj->getLocationName(),
          "labelName" => $outputObj->getLabelName(),
          "countryCode" => $countryCode,
          'dropOfTimeParcel' => $outputObj->getDropOffTimeParcel(),
          'dropOfTimeLetters' => $outputObj->getDropOffTimeLetters(),
          'dropOfTimeExpress' => $outputObj->getDropOffTimeExpress(),
          'additionalInfo' => $outputObj->getAdditionalInfo(),
          "customerServicePhoneNumber" => $outputObj->getCustomerServicePhoneNumber(),
          "openingTimes" => $outputObj->getOpeningTimes(),
          "availability" => $outputObj->getAvailability(),
          "wheelChairAccess" => $outputObj->getWheelChairAccess(),
          "pupCode" => $outputObj->getPupCode(),
          "routingCode" => $outputObj->getRoutingServiceCode(),
          "partnerType" => $outputObj->getPartnerType(),
          "location" => $outputObj->getLocationCoords(),
          'capabilities' => $outputObj->getCapabilities()
        );
      }
    }

    return $locations;
  }
}
