<?php
// TODO: write docs
namespace Mijora\Itella\Locations;

/**
 * filters locations output
 */
class Output
{
  protected $defaultLang = 'en'; // fallback language if there is no set one
  protected $lang = 'en';
  protected $rawArray = array();

  /**
   * @param array $output_arr must be valid location array
   * @param string $lang prefered language, valid options en, fi, sv (default is en if others do not exist)
   */
  public function __construct($output_arr, $lang = null)
  {
    $this->rawArray = $output_arr;

    // set prefered language
    $this->lang = $lang;
    if ($this->lang == null || !in_array(strtolower($lang), array('en', 'fi', 'sv'))) {
      $this->lang = $this->defaultLang;
    }
  }

  public function getRawArray()
  {
    return $this->rawArray;
  }

  public function getId()
  {
    return $this->rawArray['id'];
  }

  public function getType()
  {
    return $this->rawArray['type'];
  }

  public function getPostalCode()
  {
    return $this->rawArray['postalCode'];
  }

  public function getAddress()
  {
    if (isset($this->rawArray['address'][$this->lang])) {
      return $this->rawArray['address'][$this->lang];
    }

    if (isset($this->rawArray['address'][$this->defaultLang])) {
      return $this->rawArray['address'][$this->defaultLang];
    }

    return null;
  }

  public function getPublicName()
  {
    if (isset($this->rawArray['publicName'][$this->lang])) {
      return $this->rawArray['publicName'][$this->lang];
    }

    if (isset($this->rawArray['publicName'][$this->defaultLang])) {
      return $this->rawArray['publicName'][$this->defaultLang];
    }

    return null;
  }

  public function getLocationName()
  {
    if (isset($this->rawArray['locationName'][$this->lang])) {
      return $this->rawArray['locationName'][$this->lang];
    }

    if (isset($this->rawArray['locationName'][$this->defaultLang])) {
      return $this->rawArray['locationName'][$this->defaultLang];
    }

    return null;
  }

  public function getLabelName()
  {
    if (isset($this->rawArray['labelName'][$this->lang])) {
      return $this->rawArray['labelName'][$this->lang];
    }

    if (isset($this->rawArray['labelName'][$this->defaultLang])) {
      return $this->rawArray['labelName'][$this->defaultLang];
    }

    return null;
  }

  public function getCountryCode()
  {
    return $this->rawArray['countryCode'];
  }

  public function getDropOffTimeParcel()
  {
    return $this->rawArray['dropOffTimeParcel'];
  }

  public function getDropOffTimeLetters()
  {
    return $this->rawArray['dropOffTimeLetters'];
  }

  public function getDropOffTimeExpress()
  {
    return $this->rawArray['dropOffTimeExpress'];
  }

  public function getAdditionalInfo()
  {
    if (isset($this->rawArray['additionalInfo'][$this->lang])) {
      return $this->rawArray['additionalInfo'][$this->lang];
    }

    if (isset($this->rawArray['additionalInfo'][$this->defaultLang])) {
      return $this->rawArray['additionalInfo'][$this->defaultLang];
    }

    return null;
  }

  public function getCustomerServicePhoneNumber()
  {
    return $this->rawArray['customerServicePhoneNumber'];
  }

  public function getOpeningTimes()
  {
    return $this->rawArray['openingTimes'];
  }

  public function getAvailability()
  {
    return $this->rawArray['availability'];
  }

  public function getWheelChairAccess()
  {
    return $this->rawArray['wheelChairAccess'];
  }

  public function getPupCode()
  {
    return $this->rawArray['pupCode'];
  }

  public function getRoutingServiceCode()
  {
    return $this->rawArray['routingServiceCode'];
  }

  public function getPartnerType()
  {
    return $this->rawArray['partnerType'];
  }

  public function getLocationCoords()
  {
    return $this->rawArray['location'];
  }

  public function getCapabilities()
  {
    if (isset($this->rawArray['capabilities'])) {
      return $this->rawArray['capabilities'];
    }

    return $this->rawArray['capabilities'];
  }
}
