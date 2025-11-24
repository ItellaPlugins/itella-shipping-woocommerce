<?php
// TODO: write docs
namespace Mijora\Itella;

use Mijora\Itella\Shipment\Shipment as _Shipment;

class Helper
{
  public static function keyExists($key, &$arr)
  {
    if (!$arr || !is_array($arr)) {
      return false;
    }
    return (isset($arr[$key]) || array_key_exists($key, $arr));
  }

  /**
   * Returns product code by supplied tracking number. If it starts with JJFI its courier (2317) otherwise assume pickup point (2711)
   * @param string $tracking_number Tracking number to check
   */
  public static function getProductIdFromTrackNum($tracking_number)
  {
    if (strpos($tracking_number, 'JJFI') === 0) {
      return _Shipment::PRODUCT_EXPRESS_BUSINESS_DAY;
    }

    return _Shipment::PRODUCT_PARCEL_CONNECT;
  }

  /**
   * Generates reference code for COD using supplied ID (usualy order iD). ID must be min. 3 characters long for correct calculation
   * @param int|string $id
   */
  public static function generateCODReference($id)
  {
    // TODO: make sure $id is at least 2 symbols
    $weights = array(7, 3, 1);
    $sum = 0;
    $base = str_split(strval(($id)));
    $reversed_base = array_reverse($base);
    $reversed_base_length = count($reversed_base);
    for ($i = 0; $i < $reversed_base_length; $i++) {
      $sum += $reversed_base[$i] * $weights[$i % 3];
    }
    $checksum = (10 - $sum % 10) % 10;
    return implode('', $base) . $checksum;
  }

  public static function fixPhoneNumber($phone, $country_code)
  {
    // prep number
    $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
    $phone = str_replace(['-', '+'], '', $phone);

    switch (strtoupper($country_code)) {
      case 'LT':
        if ($phone[0] == 6 && strlen($phone) == 8) {
          $phone = '370' . $phone;
        }

        if (strpos($phone, '86') === 0 || strpos($phone, '06') === 0) {
          $phone = '3706' . substr($phone, 2);
        }
        break;
      case 'LV':
        if ($phone[0] == 2 && strlen($phone) == 8) {
          $phone = '371' . $phone;
        }
        break;
      case 'EE':
        $length = strlen($phone);
        if ($phone[0] == 5 && ($length == 7 || $length == 8)) {
          $phone = '372' . $phone;
        }
        if ($phone[0] == 8 && $length == 8) {
          $phone = '372' . $phone;
        }
        break;
      case 'FI':
        $length = strlen($phone);
        if (($phone[0] == 4 || $phone[0] == 5) && ($length >= 4 && $length <= 12)) {
          $phone = '358' . $phone;
        }

        // validate Fi local phone format (starting with 0)
        $pos = strpos($phone, '04');
        if ($pos === 0) {
          $phone = substr_replace($phone, '3584', $pos, strlen('04'));
        }
        $pos = strpos($phone, '05');
        if ($pos === 0) {
          $phone = substr_replace($phone, '3585', $pos, strlen('05'));
        }

        break;

      default:
        // do nothing
        break;
    }

    return '+' . $phone;
  }

  public static function get_class_name($obj)
  {
    if ($obj == null) {
      return null;
    }
    $classname = get_class($obj);
    if ($pos = strrpos($classname, '\\')) {
      return substr($classname, $pos + 1);
    }
    return $classname; // no namespace
  }
}
