<?php
// TODO: write docs
namespace Mijora\Itella\Shipment;

use Mijora\Itella\ItellaException;
use Mijora\Itella\Shipment\AdditionalService;
use Mijora\Itella\Helper;
use Mijora\Itella\Client;
use Mijora\Itella\Locations\PickupPoints;

use Pakettikauppa\Shipment as _Shipment;
use Pakettikauppa\Shipment\AdditionalService as _AdditionalService;
use Pakettikauppa\Shipment\Info as _Info;
use Pakettikauppa\Shipment\Parcel as _Parcel;

class Shipment
{
  const MULTIPARCEL_LIMIT = 10;

  // Service code (product)
  const PRODUCT_COURIER = 2317;
  const PRODUCT_PICKUP = 2711;

  // Label sizes
  const LABEL_SIZE_A5 = 'A5';
  const LABEL_SIZE_107X225 = '107x225';

  public $valid_product_codes;

  public $isTest;

  /** @var string */
  private $user;
  /** @var string */
  private $pass;

  // Main request data
  public $senderId;
  public $receiverId;

  public $documentDateTime;
  public $sequence;

  // Shipment specific
  public $shipmentNumber;
  public $shipmentDateTime; // when shipment is ready for pickup
  public $comment; // custom comment for label

  // Location pupCode
  private $pickup_point_id = false;

  /** @var int|string */
  private $product_code;

  // Party objects
  /** @var \Mijora\Itella\Shipment\Party */
  public $senderParty;
  /** @var \Mijora\Itella\Shipment\Party */
  public $receiverParty;

  // AdditionalService object storage
  /** @var \Mijora\Itella\Shipment\AdditionalService[] */
  public $additionalServices;

  // GoodsItem object storage
  /** @var \Mijora\Itella\Shipment\GoodsItem[] */
  public $goodsItems;
  /** @var int */
  public $totalItems; // counter for goods with MultiParcel service

  /** @var \Pakettikauppa\Client */
  private $_client;

  public function __construct($user, $pass, $isTest = false)
  {
    $this->isTest = $isTest;
    $this->user = $user;
    $this->pass = $pass;
    $this->product_code = null;
    $this->documentDateTime = date('c');
    $this->sequence = number_format(microtime(true), 6, '', '');
    $this->additionalServices = array();
    $this->goodsItems = array();
    $this->totalItems = 0;
    $this->comment = null;

    $this->valid_product_codes = array(
      self::PRODUCT_COURIER,
      self::PRODUCT_PICKUP
    );

    $this->_client = (new Client($this->user, $this->pass, $this->isTest))->getAuthenticatedClient();
  }

  public function asXML()
  {
    $shipment = $this->createPakettikauppaShipment();
    return $shipment->asXML();
  }

  public function registerShipment()
  {
    try {
      $shipment = $this->createPakettikauppaShipment();

      $this->_client->createTrackingCode($shipment, 'en');
      $track = $shipment->getTrackingCode();
    } catch (\Exception $e) {
      throw new ItellaException($e->getMessage());
    }

    return $track;
  }

  public function downloadLabels($track, $size = null)
  {
    if (!is_array($track)) {
      $track = array($track);
    }
    $base = $this->_client->fetchShippingLabels($track, $size);
    return $base->{'response.file'};
  }

  private function checkForMultiParcel()
  {
    if ($this->totalItems > 1) {
      // Set multi-parcel additional service
      $multi = new AdditionalService(AdditionalService::MULTI_PARCEL, array(
        'count' => $this->totalItems
      ));
      $this->addAdditionalService($multi);
    }
  }

  private function validateProductCode()
  {
    if (!$this->product_code) {
      throw new ItellaException('Shipment must have product code');
    }

    return true;
  }

  private function validateTotalGoodsItems()
  {
    $this->totalItems = count($this->goodsItems);

    if ($this->totalItems < 1) {
      throw new ItellaException('Shipment cant be empty');
    }

    if ($this->totalItems > self::MULTIPARCEL_LIMIT) {
      throw new ItellaException('Multi-parcel shipment supports max: ' . self::MULTIPARCEL_LIMIT);
    }

    return true;
  }

  private function validateShipment()
  {
    $this->validateProductCode();

    if (!$this->senderParty) {
      throw new ItellaException("Sender is not set");
    }

    if (!$this->receiverParty) {
      throw new ItellaException("Receiver is not set");
    }

    $this->validateTotalGoodsItems();
    $this->checkForMultiParcel();
  }


  /**
   * If its a pickup point shipment alters receiver info to have have pickup point address, city and postal code
   */
  private function modifyReceiverForPickupPoint()
  {
    // Only need alteration for set pickup point
    if ($this->product_code !== self::PRODUCT_PICKUP) {
      return;
    }

    // Make sure pupCode is set
    if (!$this->pickup_point_id) {
      throw new ItellaException("Shipment set for pickup point but no location pupCode given");
    }

    // Retrieve location information from locations api
    $pickup_points = new PickupPoints();
    $location = $pickup_points->getLocations([
      'countryCode' => $this->receiverParty->countryCode,
      'pupCode' => $this->pickup_point_id
    ]);

    if (!$location) {
      throw new ItellaException("Could not find location with pupCode = " . $this->pickup_point_id);
    }

    $location = $location[0];

    $address = $location['address']['address'];
    if (empty($address)) {
      $address = $location['address']['streetName'] . ' ' . $location['address']['streetNumber'];
    }

    $this->receiverParty->setStreet1($address);
    $this->receiverParty->setCity($location['address']['municipality']);
    $this->receiverParty->setPostCode($location['postalCode']);
  }

  /**
   * Creates Pakettikauppa shipment object
   */
  private function createPakettikauppaShipment()
  {
    $this->validateShipment();

    $shipment = new _Shipment();
    $shipment->setShippingMethod($this->product_code);
    $shipment->setSender($this->senderParty->getPakettikauppaParty());

    $this->modifyReceiverForPickupPoint();
    $shipment->setReceiver($this->receiverParty->getPakettikauppaParty());

    $info = new _Info();
    $info->setReference($this->shipmentNumber);
    if ($this->comment) {
      $info->setAdditionalInfoText($this->comment);
    }

    $shipment->setShipmentInfo($info);

    // add all goodsItem
    foreach ($this->goodsItems as $key => $goodsItem) {
      $parcel = new _Parcel();

      $parcel->setReference($this->shipmentNumber);
      if ($goodsItem->getGrossWeight()) {
        $parcel->setWeight($goodsItem->getGrossWeight()); // kg
      }
      if ($goodsItem->getVolume()) {
        $parcel->setVolume($goodsItem->getVolume()); // m3
      }
      if ($content_desc = $goodsItem->getContentDesc()) {
        $parcel->setContents($content_desc);
      }

      $shipment->addParcel($parcel);
    }

    // add all additional services
    foreach ($this->additionalServices as $service) {
      $_service = new _AdditionalService();

      $_service->setServiceCode($service->getCode());
      foreach ($service->getArgs() as $key => $value) {
        $_service->addSpecifier($key, $value);
      }

      $shipment->addAdditionalService($_service);
    }

    return $shipment;
  }

  /**
   * Getters
   */
  public function getDocumentDateTime()
  {
    return $this->documentDateTime;
  }

  public function getSequence()
  {
    return $this->sequence;
  }

  /**
   * Finds and returns registered additional service by code. Null if not found.
   * 
   * @return AdditionalService|null
   */
  public function getAdditionalServiceByCode($service_code)
  {
    if (Helper::keyExists($service_code, $this->additionalServices)) {
      return $this->additionalServices[$service_code];
    }

    return null;
  }

  public function getAdditionalServices()
  {
    return $this->additionalServices;
  }

  /**
   * Setters (returns this object for chainability)
   */

  /**
   * Set shipment pickup point by pupCode from Locations API
   * @var string $pickup_point_id pupCode of pickup point
   */
  public function setPickupPoint($pickup_point_id)
  {
    $this->pickup_point_id = $pickup_point_id;
    $service = new AdditionalService(
      AdditionalService::PICKUP_POINT,
      array('pickup_point_id' => $pickup_point_id)
    );

    return $this->addAdditionalService($service);
  }

  public function setProductCode($code)
  {
    if (!in_array($code, $this->valid_product_codes)) {
      throw new ItellaException('Unknown product code: ' . $code);
    }
    $this->product_code = $code;
    return $this;
  }

  public function setSenderId($senderId)
  {
    $this->senderId = $senderId;
    return $this;
  }

  public function setReceiverId($receiverId)
  {
    $this->receiverId = $receiverId;
    return $this;
  }

  public function setDocumentDateTime($documentDateTime)
  {
    $this->documentDateTime = $documentDateTime;
    return $this;
  }

  public function setSequence($sequence)
  {
    $this->sequence = $sequence;
    return $this;
  }

  public function setIsTest($isTest)
  {
    $this->isTest = $isTest;
    return $this;
  }

  public function setShipmentNumber($shipmentNumber)
  {
    $this->shipmentNumber = $shipmentNumber;
    return $this;
  }

  public function setComment($comment)
  {
    $this->comment = $comment;
    return $this;
  }

  public function setShipmentDateTime($shipmentDateTime)
  {
    $this->shipmentDateTime = $shipmentDateTime;
    return $this;
  }

  public function setSenderParty(Party $senderParty)
  {
    $this->senderParty = $senderParty;
    return $this;
  }

  public function setReceiverParty(Party $receiverParty)
  {
    $this->receiverParty = $receiverParty;
    return $this;
  }

  /**
   * @param Mijora\Itella\Shipment\AdditionalService $service
   */
  public function addAdditionalService($service)
  {
    $this->validateProductCode();

    if (!is_object($service) || Helper::get_class_name($service) != 'AdditionalService') {
      throw new ItellaException('addAdditionalService accepts only AdditionalService.');
    }
    // Check this additional service code can be set for chosen product code
    if (!$service->validateCodeByProduct($this->product_code)) {
      throw new ItellaException('Product code: ' . $this->product_code . ' doesn not support additional service code ' . $service->getCode());
    }

    // if there already is additional service with that code overwrite it
    $this->additionalServices[$service->getCode()] = $service;

    return $this;
  }

  public function addAdditionalServices($services)
  {
    if (!is_array($services)) {
      throw new ItellaException('addAdditionalServices accepts array of AdditionalService only');
    }

    foreach ($services as $service) {
      $this->addAdditionalService($service);
    }
    return $this;
  }

  public function addGoodsItem($goodsItem)
  {
    if (!is_object($goodsItem) || Helper::get_class_name($goodsItem) != 'GoodsItem') {
      throw new ItellaException('addGoodsItem accepts only GoodsItem.');
    }
    $this->goodsItems[] = $goodsItem;
    return $this;
  }

  public function addGoodsItems($goodsItems)
  {
    if (!is_array($goodsItems)) {
      throw new ItellaException('addGoodsItems accepts array of GoodsItem only');
    }

    foreach ($goodsItems as $goodsItem) {
      $this->addGoodsItem($goodsItem);
    }
    return $this;
  }
}
