<?php
// TODO: write docs
namespace Mijora\Itella\Shipment;

use Mijora\Itella\SimpleXMLElement;
use Mijora\Itella\Helper;
use Mijora\Itella\ItellaException;

use Pakettikauppa\Shipment\Sender as _Sender;
use Pakettikauppa\Shipment\Receiver as _Receiver;

class Party
{
  // constants for easier role assignment
  const ROLE_SENDER = 'CONSIGNOR';
  const ROLE_RECEIVER = 'CONSIGNEE';

  // CONSIGNOR, CONSIGNEE 
  private $role;

  // Pakettikauppa either Sender or Receiver
  private $party;

  // depending on role
  public $contract;

  // Name lines
  public $name1;
  public $name2; // if SmartPost, then name of pick-up point is given
  public $name3; // optional

  // Address lines
  public $street1;
  public $street2; // if SmartPost, then street address of pick-up point is given
  public $street3; // optional
  public $postCode; // if SmartPost, then postal code of pick-up point is given
  public $city; // SmartPost, then postal code area name of pick-up point is given ?? not all SmartPost has it
  public $countryCode; // ISO 3166-1 alpha-2 format

  // Contacts
  public $contactName; // optional
  public $contactMobile; // optional
  public $contactPhone; // optional
  public $contactEmail; // should always set in case pakettikaupa requires it

  // Should lib try and fix phone number, if set to false only checks that number format matches international standart
  public $checkPhone = true;

  public function __construct($role)
  {
    if ($role != self::ROLE_RECEIVER && $role != self::ROLE_SENDER) {
      throw new ItellaException("Bad role for party");
    }

    $this->role = $role;

    $this->party = ($role == self::ROLE_RECEIVER ? new _Receiver() : new _Sender());
  }

  private function isCountrySet()
  {
    if (!$this->countryCode) {
      throw new ItellaException("Country code must be set");
    }

    return true;
  }

  private function validatePhone($phone)
  {
    $country = $this->checkPhone ? strtoupper($this->countryCode) : 'GLOBAL';

    // set regex
    switch ($country) {
      case 'LT':
        $regex = '/^\+3706[0-9]{7}$/';
        break;
      case 'LV':
        $regex = '/^\+3712[0-9]{7}$/';
        break;
      case 'EE':
        $regex = '/^\+372[5,8][0-9]{6,7}$/';
        break;
      case 'FI':
        $regex = '/^\+358(?:4.|50)[0-9]{4,8}$/';
        break;
      // default, should be able to handle any phone number
      default:
      $regex = '/^\+[0-9]{1,3}[0-9]{4,13}$/';
        break;
    }

    if (!preg_match($regex, $phone)) {
      if ($this->checkPhone) {
        $message = "Invalid phone number supplied for " . strtoupper($this->countryCode) . " country.";
      } else {
        $message = "Supplied phone number does not match international format.";
      }

      throw new ItellaException($message . " Tested phone number: " . $phone);
    }

    return true;
  }

  public function getPakettikauppaParty()
  {
    return $this->party;
  }

  /**
   * Setters (returns this object for chainability)
   */
  public function setContract($contract)
  {
    if ($this->role != self::ROLE_SENDER) {
      throw new ItellaException("Contract is only for ROLE_SENDER role");
    }
    $this->contract = $contract;
    $this->party->setContractId($contract);
    return $this;
  }

  public function setName1($name1)
  {
    $this->name1 = $name1;
    $this->party->setName1($name1);
    return $this;
  }

  public function setName2($name2)
  {
    $this->name2 = $name2;
    $this->party->setName2($name2);
    return $this;
  }

  // Pakettikauppa doesnt have name3
  public function setName3($name3)
  {
    $this->name3 = $name3;
    return $this;
  }

  public function setStreet1($street1)
  {
    $this->street1 = $street1;
    $this->party->setAddr1($street1);
    return $this;
  }

  public function setStreet2($street2)
  {
    $this->street2 = $street2;
    $this->party->setAddr2($street2);
    return $this;
  }

  public function setStreet3($street3)
  {
    $this->street3 = $street3;
    $this->party->setAddr3($street3);
    return $this;
  }

  public function setPostCode($postCode)
  {
    $this->postCode = $postCode;
    $this->party->setPostcode($postCode);
    return $this;
  }

  public function setCity($city)
  {
    $this->city = $city;
    $this->party->setCity($city);
    return $this;
  }

  public function setCountryCode($countryCode)
  {
    $countryCode = strtoupper($countryCode);
    $this->countryCode = $countryCode;
    $this->party->setCountry($countryCode);
    return $this;
  }

  // Pakettikauppa doesnt have contact name
  public function setContactName($contactName)
  {
    $this->contactName = $contactName;
    return $this;
  }

  // Pakettikauppa uses jus one phone for contact
  public function setContactMobile($contactMobile)
  {
    $this->isCountrySet();
    if ($this->checkPhone) {
      $contactMobile = Helper::fixPhoneNumber($contactMobile, $this->countryCode);
    }
    $this->validatePhone($contactMobile);

    $this->contactMobile = $contactMobile;
    $this->party->setPhone($contactMobile);
    return $this;
  }

  public function setContactPhone($contactPhone)
  {
    $this->isCountrySet();
    if ($this->checkPhone) {
      $contactPhone = Helper::fixPhoneNumber($contactPhone, $this->countryCode);
    }
    $this->validatePhone($contactPhone);

    $this->contactPhone = $contactPhone;
    $this->party->setPhone($contactPhone);
    return $this;
  }

  public function setContactEmail($contactEmail)
  {
    $this->contactEmail = $contactEmail;
    $this->party->setEmail($contactEmail);
    return $this;
  }

  public function disablePhoneCheck()
  {
    $this->checkPhone = false;
    return $this;
  }

  public function enablePhoneCheck()
  {
    $this->checkPhone = true;
    return $this;
  }
}
