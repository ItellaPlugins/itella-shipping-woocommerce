<?php
// TODO: write docs
namespace Mijora\Itella\Locations;

class PickupPoints
{
  public $api_url = 'https://delivery.plugins.itella.com/api/locations';
  private $lang;
  private $error_msg = '';

  public function __construct($api_url = false, $lang = null)
  {
    if ( ! empty($api_url) ) {
      $this->api_url = $api_url;
    }
    $this->lang = $lang;
  }

  /**
   * Save locations string to file
   * 
   * @param string $filename
   * @param string $locations
   */
  public function saveLocationsToJSONFile($filename, $locations)
  {
    $fp = fopen($filename, 'w');
    fwrite($fp, $locations);
    fclose($fp);
  }

  public function loadLocationsFromJSONFile($filename)
  {
    $fp = fopen($filename, "r");
    $terminals = fread($fp, filesize($filename) + 10);
    fclose($fp);
    return json_decode($terminals, true);
  }

  /**
   * @param string $iso_code2
   * 
   * @return array associated array of JSON response
   */
  public function getLocationsByCountry($iso_code2)
  {
    return $this->getLocations(['countryCode' => strtoupper($iso_code2)]);
  }

  /**
   * @return array associated array of JSON response
   */
  public function getLocations($args = false)
  {
    $query = '';
    if ($args) {
      foreach ($args as $key => $arg) {
        $query .= '&' . $key . '=' . $arg;
      }
    }
    // limit what types we request
    foreach(array('SMARTPOST', 'LOCKER', 'POSTOFFICE', 'PICKUPPOINT') as $type) {
      $query .= '&types=' . $type;
    }
    $url = $this->api_url . ($query ? '?' . $query : '');

    $result = CurlRequest::doCurlRequest($url);
    if (isset($result['error'])) {
      $this->error_msg = $result['error'];
      return false;
    }

    $locations = CurlRequest::getLocations($result, $this->lang);

    if (isset($locations['error'])) {
      $this->error_msg = $locations['error'];
      return false;
    }

    return $locations;
  }

  public function getErrorMsg()
  {
    return $this->error_msg;
  }
}
