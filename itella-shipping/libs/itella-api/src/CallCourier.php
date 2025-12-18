<?php
// TODO: To be decided if library should handle this or not as pakettikauppa does not have this functionality at the moment
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;
use Mijora\Itella\Client;
use Mijora\Itella\ItellaPickupsApi;

class CallCourier
{
  private $username = '';
  private $password = '';
  private $itella_email;
  private $sender_email;
  private $attachment = false; // attachement
  private $isTest = false;
  private $pickupAddress = array(
    'sender' => '',
    'address_1' => '',
    'postcode' => '',
    'city' => '',
    'country' => '',
    'pickup_time' => '',
    'contact_phone' => '',
  );
  private $pickupParams = array(
    'date' => '',
    'time_from' => '08:00',
    'time_to' => '17:00',
    'info_general' => '',
    'id_sender' => '',
  );
  private $subject = 'Call Itella Courier';
  private $items = array();
  private $translates = array(
    'mail_title' => 'Pickup information',
    'mail_sender' => 'Sender',
    'mail_address' => 'Address (pickup from)',
    'mail_phone' => 'Contact Phone',
    'mail_pickup_time' => 'Pickup time',
    'mail_attachment' => 'See attachment for manifest PDF.',
  );
  private $all_methods = array(
    'api',
    'postra',
    'email'
  );
  private $enabled_methods = array();
  private $show_prefix = false;

  public function __construct($itella_email = '', $isTest = false)
  {
    $this->itella_email = $itella_email;
    $this->isTest = $isTest;

    foreach ($this->all_methods as $method) {
      $this->enableMethod($method);
    }
  }

  /***************************************
   * General
   **************************************/

  public function callCourier()
  {
    $messages = array(
      'errors' => array(),
      'success' => array()
    );
    
    try {
      foreach ($this->enabled_methods as $method) {
        switch ($method) {
          case 'api':
            if (!$this->isApiMethodAvailable()) {
              continue 2;
            }
            $result = $this->callApiCourier();
            $prefix = 'API';
            break;
          case 'postra':
            if (!$this->isPostraMethodAvailable()) {
              continue 2;
            }
            $result = $this->callPostraCourier();
            $prefix = 'POSTRA';
            break;
          case 'email':
            if (!$this->isEmailMethodAvailable()) {
              continue 2;
            }
            $result = $this->callMailCourier();
            $prefix = 'EMAIL';
            break;
          default:
            continue 2;
        }
        if ($this->getCallError($result)) {
          $messages['errors'][] = $this->getCallError($result, $prefix);
        } else {
          $messages['success'][] = $this->getCallSuccess($result, $prefix);
        }
      }
    } catch (\Exception $e) {
      $prefix = ($this->show_prefix) ? 'ERROR: ' : '';
      $messages['errors'][] = $prefix . $e->getMessage();
    }
    
    return $messages;
  }

  private function isLoginsCorrect()
  {
    $username = trim($this->getUsername());
    $password = trim($this->getPassword());

    if (empty($username) || empty($password)) {
      return false;
    }

    return true;
  }

  private function isApiMethodAvailable()
  {
    $not_allowed_countries = array('LT');

    if (in_array($this->pickupAddress['country'], $not_allowed_countries)) {
      return false;
    }

    return true;
  }

  private function isPostraMethodAvailable()
  {
    $allowed_countries = array('LT');

    if (!in_array($this->pickupAddress['country'], $allowed_countries)) {
      return false;
    }

    return true;
  }

  private function isEmailMethodAvailable()
  {
    return true;
  }

  private function getCallError($result, $prefix = '')
  {
    $prefix = ($this->show_prefix) ? $prefix : '';
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    if (!is_array($result)) {
      return $prefix . $result;
    }
    if (!array_key_exists('message', $result)) {
      $result['message'] = 'Unknown error';
    }
    if (!array_key_exists('status', $result)) {
      return $prefix . 'Unknown result status';
    }
    if ($result['status'] != '200') {
      return $prefix . $result['message'];
    }
    return false;
  }

  private function getCallSuccess($result, $prefix = '')
  {
    $prefix = ($this->show_prefix) ? $prefix : '';
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $message = (array_key_exists('message', $result)) ? $result['message'] : 'Success';
    return $prefix . $message;
  }

  /***************************************
   * Method: Email
   **************************************/

  /**
   * Sends email using mail() even if successfull does not mean mail will reach recipient
   * 
   * @throws Exception when mail fails to register for sending
   */
  public function callMailCourier()
  {
    if (!headers_sent()) {
      // Force PHP to use the UTF-8 charset
      header('Content-Type: text/html; charset=utf-8');
    }

    $uid = md5(uniqid(time()));
    // Define and Base64 encode the subject line
    $subject_text = ($this->isTest ? 'TEST CALL - ' : '') . $this->subject;
    $subject = '=?UTF-8?B?' . base64_encode($subject_text) . '?=';

    $eol = "\r\n";

    $headers = '';
    $message = '';
    // Add custom headers
    $from = str_replace(array("\r", "\n"), '', $this->sender_email);
    $headers .= "From: $from$eol";
    $headers .= "MIME-Version: 1.0$eol";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$uid\"$eol";
    $headers .= $eol;
    $message .= "--" . $uid . "$eol";
    $message .= "Content-Type: text/html; charset=utf-8$eol";
    $message .= "Content-Transfer-Encoding: base64" . $eol . $eol;
    // Base64 the email message
    $message .= rtrim(chunk_split(base64_encode($this->buildMailBody()))) . "$eol";
    if ($this->attachment) {
      $message .= "--" . $uid . "$eol";
      $message .= "Content-Type: application/octet-stream; name=\"manifest.pdf\"$eol";
      $message .= "Content-Transfer-Encoding: base64$eol";
      $message .= "Content-Disposition: attachment; filename=\"manifest.pdf\"" . $eol . $eol;
      $message .= rtrim(chunk_split($this->attachment)) . "$eol";
    } else {
      return array(
        'status' => '500',
        'message' => 'No manifest attached to courier call email'
      );
    }
    $message .= "--" . $uid . "--";
    // Send mail with custom headers
    if (!mail($this->itella_email, $subject, $message, $headers)) {
      return array(
        'status' => '500',
        'message' => 'Failed to send email via mail() function'
      );
    }

    return array(
      'status' => '200',
      'message' => 'The call email has been successfully sent to the courier'
    );
  }

  public function buildMailBody()
  {
    $this->checkPickupTime();

    $body = '<h2>' . $this->translates['mail_title'] . '</h2><br/>' . PHP_EOL;
    $body .= '<table>' . PHP_EOL;
    if (!empty($this->pickupAddress['sender'])) {
      $body .= "<tr><td>" . $this->translates['mail_sender'] . ":</td><td>" . $this->pickupAddress['sender'] . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['address_1'])) {
      $address = $this->pickupAddress['address_1'];
      $address .= (!empty($this->pickupAddress['postcode'])) ? ', ' . $this->pickupAddress['postcode'] : '';
      $address .= (!empty($this->pickupAddress['city'])) ? ', ' . $this->pickupAddress['city'] : '';
      $address .= (!empty($this->pickupAddress['country'])) ? ', ' . $this->pickupAddress['country'] : '';
      $body .= "<tr><td>" . $this->translates['mail_address'] . ":</td><td>" . $address . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['contact_phone'])) {
      $body .= "<tr><td>" . $this->translates['mail_phone'] . ":</td><td>" . $this->pickupAddress['contact_phone'] . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['pickup_time'])) {
      $body .= "<tr><td>" . $this->translates['mail_pickup_time'] . ":</td><td>" . $this->pickupAddress['pickup_time'] . "</td></tr>" . PHP_EOL;
    }
    $body .= '</table>' . PHP_EOL;
    if ($this->attachment) {
      $body .= "<br/>" . $this->translates['mail_attachment'] . PHP_EOL;
    }
    return $body;
  }

  /***************************************
   * Method: API
   **************************************/

  public function callApiCourier()
  {
    $ItellaPickupsApi = new ItellaPickupsApi();
    $response = $ItellaPickupsApi->request('pickups', $this->buildApiRequest());

    return $response ? $response : false;
  }

  public function buildApiRequest()
  {
    $this->checkPickupTime();
    
    $data = array();
    
    $data['sender']['name'] = (!empty($this->pickupAddress['sender'])) ? $this->pickupAddress['sender'] : '';
    $data['sender']['email'] = (!empty($this->sender_email)) ? $this->sender_email : '';
    $data['sender']['address_1'] = (!empty($this->pickupAddress['address_1'])) ? $this->pickupAddress['address_1'] : '';
    $data['sender']['postcode'] = (!empty($this->pickupAddress['postcode'])) ? $this->pickupAddress['postcode'] : '';
    $data['sender']['city'] = (!empty($this->pickupAddress['city'])) ? $this->pickupAddress['city'] : '';
    $data['sender']['country'] = (!empty($this->pickupAddress['country'])) ? $this->pickupAddress['country'] : '';
    $data['sender']['phone'] = (!empty($this->pickupAddress['contact_phone'])) ? $this->pickupAddress['contact_phone'] : '';
    $data['pickup_time'] = (!empty($this->pickupAddress['pickup_time'])) ? $this->pickupAddress['pickup_time'] : '';
    $data['items'] = (!empty($this->items)) ? $this->items : array();

    return $data;
  }

  /***************************************
   * Method: POSTRA
   **************************************/

  public function callPostraCourier()
  {
    $url = 'https://connect.posti.fi/transportation/v1/';
    if ($this->isTest) {
      $url = 'https://connect.ja.posti.fi/kasipallo/transportation/v1/';
    }

    if (!$this->isPostraMethodAvailable()) {
      return array(
        'status' => '500',
        'message' => 'Call via POSTRA is not allowed',
      );
    }

    if (!$this->isLoginsCorrect()) {
      return array(
        'status' => '500',
        'message' => 'Invalid API logins',
      );
    }

    $client = new Client($this->getUsername(), $this->getPassword(), $this->isTest);
    $token = $client->getAccessToken();

    $ItellaPickupsApi = new ItellaPickupsApi($url);
    $ItellaPickupsApi->setToken($token);
    return $ItellaPickupsApi->requestXml('orders', $this->buildPostraXml());
  }

  private function buildPostraXml()
  {
    $date = $this->getPickupParamsValue('date', date('Y-m-d', strtotime('+1 day')));
    $payer_data = $this->getPostraPayerData();

    $xml = new \SimpleXMLElement('<Postra/>');
    $xml->addAttribute('xmlns', 'http://api.posti.fi/xml/POSTRA/1');

    $header = $xml->addChild('Header');
    $header->addChild('SenderId', $this->getPickupParamsValue('id_sender'));
    $header->addChild('ReceiverId', '003715318644');
    $header->addChild('DocumentDateTime', gmdate('c'));
    $header->addChild('Sequence', floor(microtime(true) * 1000));
    $header->addChild('MessageCode', 'POSTRA');
    $header->addChild('MessageVersion', 1);
    $header->addChild('MessageRelease', 2);
    $header->addChild('MessageAction', 'PICKUP_ORDER');

    $shipments = $xml->addChild('Shipments');
    foreach ($this->items as $shipment_data) {
      $track_numbers = (is_array($shipment_data['track_num'])) ? $shipment_data['track_num'] : array($shipment_data['track_num']);
      $shipment = $shipments->addChild('Shipment');
      $shipment->addChild('MessageFunctionCode', 'ORIGINAL');
      $shipment->addChild('PickupOrderType', 'PICKUP');
      $shipment->addChild('ShipmentNumber', $track_numbers[0] . 'X');
      $shipment->addChild('ShipmentDateTime', gmdate('c'));

      $pickup = $shipment->addChild('PickupDate', $date);
      $pickup->addAttribute('timeEarliest', gmdate('H:i:sP', strtotime($date . ' ' . $this->getPickupParamsValue('time_from'))));
      $pickup->addAttribute('timeLatest', gmdate('H:i:sP', strtotime($date . ' ' . $this->getPickupParamsValue('time_to'))));

      $general_instruction_values = array();
      if (!empty($this->getPickupParamsValue('info_general'))) {
        $general_instruction_values[] = $this->getPickupParamsValue('info_general');
      }
      foreach ($track_numbers as $track_num) {
        $general_instruction_values[] = $track_num;
      }
      if (!empty($general_instruction_values)) {
        $instructions = $shipment->addChild('Instructions');
        $instruction = $instructions->addChild('Instruction', implode(',', $general_instruction_values));
        $instruction->addAttribute('type', 'GENERAL');
      }

      $postcode = $this->sanitizePostcode($this->getPickupAddressValue('postcode'));

      $parties = $shipment->addChild('Parties');
      $consignor = $parties->addChild('Party');
      $consignor->addAttribute('role', 'CONSIGNOR');
      $consignor->addChild('Name1', $this->getPickupAddressValue('sender'));
      $consignor_location = $consignor->addChild('Location');
      $consignor_location->addChild('Street1', $this->getPickupAddressValue('address_1'));
      $consignor_location->addChild('Postcode', $postcode);
      $consignor_location->addChild('City', $this->getPickupAddressValue('city'));
      $consignor_location->addChild('Country', $this->getPickupAddressValue('country'));
      $consignor_contact = $consignor->addChild('ContactChannel', $this->getPickupAddressValue('contact_phone'));
      $consignor_contact->addAttribute('channel', 'MOBILE');
      $payer = $parties->addChild('Party');
      if (!empty($payer_data['name'])) {
        $payer->addAttribute('role', 'PAYER');
        if (!empty($payer_data['customer'])) {
          $account1 = $payer->addChild('Account', $payer_data['customer']);
          $account1->addAttribute('type', 'SAP_CUSTOMER');
        }
        if (!empty($payer_data['invoice'])) {
          $account2 = $payer->addChild('Account', $payer_data['invoice']);
          $account2->addAttribute('type', 'SAP_INVOICE');
        }
        $payer->addChild('Name1', $payer_data['name']);
      }

      $items = $shipment->addChild('GoodsItems');
      $item = $items->addChild('GoodsItem');
      $qty = $item->addChild('PackageQuantity', 1);
      $qty->addAttribute('type', 'CW');
    }

    return $xml->asXML();
  }

  private function getPostraPayerData()
  {
    $payers = array(
      'LT' => array(
        'customer' => '919643',
        'invoice' => '',
        'name' => 'SmartPosti UAB'
      ),
      'LV' => array(
        'customer' => '919641',
        'invoice' => '',
        'name' => 'SmartPosti SIA'
      )
    );

    $country = strtoupper($this->getPickupAddressValue('country'));
    return (isset($payers[$country])) ? $payers[$country] : $payers['LT'];
  }

  /***************************************
   * Helpers
   **************************************/

  private function checkPickupTime()
  {
    if (empty($this->pickupAddress['pickup_time'])) {
      $this->pickupAddress['pickup_time'] = $this->pickupParams['date'] . ' ' . $this->pickupParams['time_from'] . ' - ' . $this->pickupParams['time_to'];
    }
  }

  private function getPickupAddressValue( $value_key, $empty_value = '' )
  {
    if (isset($this->pickupAddress[$value_key])) {
      $value = $this->pickupAddress[$value_key];
      if ( is_string($value) ) {
        $value = $this->convertToASCII($value);
      }
      if ($value !== '' && $value !== null && $value !== false) {
        return $value;
      }
    }

    return $empty_value;
  }

  private function getPickupParamsValue( $value_key, $empty_value = '' )
  {
    if (isset($this->pickupParams[$value_key])) {
      $value = $this->pickupParams[$value_key];
      if ( is_string($value) ) {
        $value = $this->convertToASCII($value);
      }
      if ($value !== '' && $value !== null && $value !== false) {
        return $value;
      }
    }

    return $empty_value;
  }

  private function sanitizePostcode( $postcode )
  {
    $postcode = trim($postcode);
    $postcode = preg_replace('/[^0-9\-]/', '', $postcode); // Allow only numbers and dash
    $postcode = preg_replace('/(?<!\d)\-|\-(?!\d)/', '', $postcode); // Remove all dashes that are not between numbers

    if ( ! preg_match('/^[0-9]+(\-[0-9]+)?$/', $postcode) ) {
      $postcode = preg_replace('/[^0-9]/', '', $postcode); // Remove dash if it is not in the middle of number
    }

    return $postcode;
  }

  private function convertToASCII( $string )
  {
    $map = [
      'a' => ['ą','ā','ä','å','à','á','â','ã','ă','ǎ','ȧ','ȁ','ạ','ả','ą','ȃ','ǻ','ă','å','ǡ','ǻ','ǟ','ǡ','ǡ'],
      'A' => ['Ą','Ā','Ä','Å','À','Á','Â','Ã'],
      'c' => ['č','ç'],
      'C' => ['Č','Ç'],
      'e' => ['ę','ė','ē','è','é','ê','ë'],
      'E' => ['Ę','Ė','Ē','È','É','Ê','Ë'],
      'g' => ['ģ'],
      'G' => ['Ģ'],
      'i' => ['į','ī','ì','í','î','ï'],
      'I' => ['Į','Ī','Ì','Í','Î','Ï'],
      'k' => ['ķ'],
      'K' => ['Ķ'],
      'l' => ['ļ','ł'],
      'L' => ['Ļ','Ł'],
      'n' => ['ņ','ñ'],
      'N' => ['Ņ','Ñ'],
      'o' => ['õ','ö','ø','ò','ó','ô','õ','ő','ō'],
      'O' => ['Õ','Ö','Ø','Ò','Ó','Ô','Õ','Ő','Ō'],
      's' => ['š'],
      'S' => ['Š'],
      'u' => ['ų','ū','ü','ù','ú','û'],
      'U' => ['Ų','Ū','Ü','Ù','Ú','Û'],
      'z' => ['ž','ź','ż'],
      'Z' => ['Ž','Ź','Ż'],
    ];

    $string = str_replace('ß', 'ss', $string);

    foreach ( $map as $latin => $chars ) {
      $string = str_replace($chars, $latin, $string);
    }

    return $string;
  }

  /***************************************
   * Setters and Getters
   **************************************/

  public function setUsername($username)
  {
    $this->username = $username;
    return $this;
  }

  private function getUsername()
  {
    return $this->username;
  }

  public function setPassword($password)
  {
    $this->password = $password;
    return $this;
  }

  private function getPassword()
  {
    return $this->password;
  }

  /**
   * $pickup = array(
   *  'sender' => 'Name / Company name',
   *  'address_1' => 'Street st. 1',
   *  'postcode' => '12345',
   *  'city' => 'City name',
   *  'country' => 'LT',
   *  'pickup_time' => '2025-05-15 8:00 - 17:00',
   *  'contact_phone' => '+37060000000',
   * );
   */
  public function setPickUpAddress($pickupAddress)
  {
    $this->pickupAddress = array_merge($this->pickupAddress, $pickupAddress);
    return $this;
  }

  /**
   * $params = array(
   *  'date' => '2001-12-20',
   *  'time_from' => '08:00',
   *  'time_to' => '17:00',
   *  'info_general' => 'Message to courier',
   *  'id_sender' => '123',
   * );
   */
  public function setPickUpParams($pickupParams)
  {
    $this->pickupParams = array_merge($this->pickupParams, $pickupParams);
    return $this;
  }

  public function setSenderEmail($sender_email)
  {
    $this->sender_email = $sender_email;
    return $this;
  }

  public function setSubject($subject)
  {
    $this->subject = $subject;
    return $this;
  }

  public function setAttachment($attachment, $isBase64 = false)
  {
    $this->attachment = ($isBase64 ? $attachment : base64_encode($attachment));
    return $this;
  }

  /**
   * $items = array(
   *  array(
   *    'track_num' => '01234567890123456789',
   *    'weight' => '1.0',
   *    'amount' => '1',
   *    'delivery_address' => 'Name / Company name. Street, Postcode City, Country',
   *  ),
   * );
   */
  public function setItems($items = [])
  {
    $this->items = $items;
    return $this;
  }

  public function setTranslates($translates)
  {
    $this->translates = array_merge($this->translates, $translates);
    return $this;
  }

  public function showMessagesPrefix($show)
  {
    $this->show_prefix = (bool)$show;
    return $this;
  }

  public function enableMethod($method)
  {
    $this->enabled_methods[] = $method;
    return $this;
  }

  public function disableMethod($method)
  {
    foreach ($this->enabled_methods as $key => $enabled_method) {
      if ($enabled_method == $method) {
        unset($this->enabled_methods[$key]);
      }
    }
    return $this;
  }
}
