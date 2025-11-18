<?php
// TODO: write docs
namespace Mijora\Itella\Shipment;

use Mijora\Itella\Helper;
use Mijora\Itella\ItellaException;

/**
 * Pakettikauppa expects:
 * 2106 = Pickup point, specifiers: [pickup_point_id]
 * 3101 = Cash On Delivery, specifiers: [amount, account, reference, codbic]
 * 3102 = Multi-parcel shipment, specifiers [count]
 * 3104 = Fragile
 * 3174 = Large (Oversized)
 */
class AdditionalService
{
  /**
   * Available additional services
   */
  const COD = 3101;
  const MULTI_PARCEL = 3102;
  const FRAGILE = 3104;
  const CALL_BEFORE_DELIVERY = 3166;
  const OVERSIZED = 3174;
  //const PICKUP_POINT = 3201;
  const PICKUP_POINT = 2106; // pakettikauppa pickup point service

  private static $valid_code = array(
    self::COD, self::MULTI_PARCEL, self::FRAGILE,
    self::CALL_BEFORE_DELIVERY, self::OVERSIZED,
    self::PICKUP_POINT
  );

  private static $valid_by_product_code = array(
    '2317' => array(
      self::COD, self::MULTI_PARCEL, self::FRAGILE,
      self::CALL_BEFORE_DELIVERY, self::OVERSIZED
    ),
    '2104' => array(
      self::COD, self::MULTI_PARCEL
    ),
    '2711' => array(
      self::PICKUP_POINT, self::COD
    ),
    '2103' => array(
      self::PICKUP_POINT, self::COD
    )
  );

  private $code; // Additional service code
  private $args; // required information for chosen code

  public function __construct($code, $args = array())
  {
    if (!in_array($code, self::$valid_code)) {
      throw new ItellaException('Invalid additional service code.');
    }

    $this->code = $code;
    $this->validateArgs($code, $args);
    $this->args = $args;
  }

  /**
   * Checks additional service code if its available for supplied product code
   * 
   * @param string|int $product_code
   * @return bool
   * 
   * @throws ItellaException
   */
  public function validateCodeByProduct($product_code)
  {
    if (!self::getCodesByProduct($product_code)) {
      throw new ItellaException('Unsupported product code: ' . $product_code);
    }
    return in_array($this->code, self::getCodesByProduct($product_code));
  }

  private function validateArgs($code, $args)
  {
    switch ($code) {
      case self::COD:
        // Cash On Delivery, specifiers: [amount, account, reference, codbic]
        $must_have = array('amount', 'account', 'reference', 'codbic');
        foreach ($must_have as $key) {
          if (!Helper::keyExists($key, $args) || empty($args[$key])) {
            throw new ItellaException(self::COD . ' code must have: ' . implode(', ', $must_have));
          }
        }
        break;
      case self::MULTI_PARCEL:
        // Multi-parcel shipment, specifiers [count]
        $must_have = array('count');
        foreach ($must_have as $key) {
          if (!Helper::keyExists($key, $args) || empty($args[$key])) {
            throw new ItellaException(self::MULTI_PARCEL . ' code must have: ' . implode(', ', $must_have));
          }
        }
        break;
      case self::FRAGILE:
        // nothing to vilidate at this time
        break;
      case self::CALL_BEFORE_DELIVERY:
        // nothing to vilidate at this time
        break;
      case self::OVERSIZED:
        // nothing to vilidate at this time
        break;
      case self::PICKUP_POINT:
        // Pickup point, specifiers: [pickup_point_id]
        $must_have = array('pickup_point_id');
        foreach ($must_have as $key) {
          if (!Helper::keyExists($key, $args) || empty($args[$key])) {
            throw new ItellaException(self::PICKUP_POINT . ' code must have: ' . implode(', ', $must_have));
          }
        }
        break;

      default:
        throw new ItellaException('Unknown additional code');
        break;
    }
  }

  public static function getCodesByProduct($product_code)
  {
    if (!isset(self::$valid_by_product_code[$product_code])) {
      return false;
    }
    return self::$valid_by_product_code[$product_code];
  }

  public function getCode()
  {
    return $this->code;
  }

  public function getArgs()
  {
    return $this->args;
  }
}
