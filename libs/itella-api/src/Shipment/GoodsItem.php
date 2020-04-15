<?php
// TODO: Cleanup
// TODO: write docs
namespace Mijora\Itella\Shipment;

use Mijora\Itella\ItellaException;
use Mijora\Itella\SimpleXMLElement;

class GoodsItem
{
  //public $packageQuantity = 1;
  //public $productId;
  public $grossWeight; // kg
  public $volume; // m3
  public $content_desc; // optional, content description
  //public $services = [];
  //public $trackingNumber;

  public function __construct()
  {
    // product id moved to Shipment
  }

  /**
   * Main functions
   */
  /**
   * If $root is supplied this will atach to it instead of creating new SimpleXMLElement
   * @param SimpleXMLElement $root
   */
  // public function getXML($root = false)
  // {
  //   if (is_subclass_of($root, 'SimpleXMLElement')) {
  //     $xml = $root->addChild('GoodsItem');
  //   } else {
  //     $xml = new SimpleXMLElement('<GoodsItem/>');
  //   }

  //   $xml->addChild('PackageQuantity', $this->packageQuantity)->addAttribute('type', 'PC');
  //   if ($this->grossWeight !== null)
  //     $xml->addChild('GrossWeight', $this->grossWeight);
  //   if ($this->volume !== null)
  //     $xml->addChild('Volume', $this->volume);

  //   $xml->addChild('Product', $this->productId);

  //   if (count($this->services) > 0) {
  //     $services = $xml->addChild('Services');
  //     foreach ($this->services as $service) {
  //       $services->addChild('Service', $service);
  //     }
  //   }

  //   $xml->addChild('Packages')
  //     ->addChild('Package')
  //     ->addChild('TrackingNumber', $this->trackingNumber)->addAttribute('type', 'POSTI');

  //   return $xml;
  // }

  // public function validateExtraServices($services)
  // {
  //   // make array if its a single service
  //   if (!is_array($services)) {
  //     $services = array($services);
  //   }

  //   foreach ($services as $service) {
  //     switch ($this->productId) {
  //       case self::PRODUCT_COURIER:
  //         if (!in_array($service, array('3101', '3102', '3104', '3166', '3174'))) {
  //           throw new \Exception("Not supported extra service: " . $service, 2317);
  //         }
  //         break;

  //       case self::PRODUCT_PICKUP:
  //         if ($service != '3201') {
  //           throw new \Exception("Pickup Point accepts only 3201 extra service", 2711);
  //         }
  //         break;
  //     }
  //   }
  // }

  // public function hasExtraService($service)
  // {
  //   return (count($this->services) > 0 && in_array($service, $this->services));
  // }

  /**
   * Setters (returns this object for chainability)
   */
  // public function setPackageQuantity($packageQuantity)
  // {
  //   $this->packageQuantity = $packageQuantity;
  //   return $this;
  // }

  public function setGrossWeight($grossWeight)
  {
    if (filter_var($grossWeight, FILTER_VALIDATE_FLOAT) === false) {
      throw new ItellaException("Invalid grossWeight");
    }
    $this->grossWeight = $grossWeight;
    return $this;
  }

  public function setVolume($volume)
  {
    if (filter_var($volume, FILTER_VALIDATE_FLOAT) === false) {
      throw new ItellaException("Invalid volume");
    }
    $this->volume = $volume;
    return $this;
  }

  public function setContentDesc($content_desc)
  {
    $content_desc = filter_var($content_desc, FILTER_SANITIZE_STRING);
    $this->content_desc = $content_desc;
    return $this;
  }

  /**
   * Getters
   */

  public function getGrossWeight()
  {
    return $this->grossWeight;
  }

  public function getVolume()
  {
    return $this->volume;
  }

  public function getContentDesc()
  {
    return $this->content_desc;
  }

  // public function addExtraService($serviceId)
  // {
  //   $this->validateExtraServices($serviceId); // thrws error if non valid service id is found
  //   // can be passed array of extra services
  //   if (is_array($serviceId)) {
  //     $this->services = array_unique(array_merge($this->services, $serviceId));
  //     return $this;
  //   }

  //   if (!in_array($serviceId, $this->services)) {
  //     $this->services[] = $serviceId;
  //   }
  //   return $this;
  // }

  // public function setTrackingNumber($trackingNumber)
  // {
  //   $this->trackingNumber = $trackingNumber;
  //   return $this;
  // }
}
