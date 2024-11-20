<?php
// TODO: write docs
namespace Mijora\Itella\Shipment;

use Mijora\Itella\ItellaException;
use Mijora\Itella\SimpleXMLElement;

class GoodsItem
{
  public $grossWeight; // kg
  public $volume; // m3
  public $content_desc; // optional, content description

  public function __construct()
  {
    // product id moved to Shipment
  }

  /**
   * Main functions
   */

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
}
