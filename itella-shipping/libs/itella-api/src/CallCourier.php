<?php
// TODO: To be decided if library should handle this or not as pakettikauppa does not have this functionality at the moment
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;
use Mijora\Itella\ItellaPickupsApi;

class CallCourier
{
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

  public function __construct($itella_email, $isTest = false)
  {
    $this->itella_email = $itella_email;
    $this->isTest = $isTest;
  }

  public function callCourier()
  {
    try {
      $this->callApiCourier();
    } catch (\Exception $e) {
      // Ignore this
    }
    return $this->callMailCourier();
  }

  /**
   * Sends email using mail() even if successfull does not mean mail will reach recipient
   * 
   * @throws Exception when mail fails to register for sending
   */
  public function callMailCourier()
  {
    // Force PHP to use the UTF-8 charset
    header('Content-Type: text/html; charset=utf-8');

    $uid = md5(uniqid(time()));
    // Define and Base64 encode the subject line
    $subject_text = ($this->isTest ? 'TEST CALL - ' : '') . $this->subject;
    $subject = '=?UTF-8?B?' . base64_encode($subject_text) . '?=';

    $eol = PHP_EOL;

    $headers = '';
    $message = '';
    // Add custom headers
    $headers .= "From: " . $this->sender_email . "$eol";
    $headers .= "MIME-Version: 1.0$eol";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$uid\"$eol";
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
      throw new ItellaException('No manifest attached to call courier');
    }
    $message .= "--" . $uid . "--";
    // Send mail with custom headers
    if (!mail($this->itella_email, $subject, $message, $headers)) {
      throw new ItellaException('Oops, something gone wrong!');
    }

    return true;
  }

  public function buildMailBody()
  {
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

  public function callApiCourier()
  {
    $ItellaPickupsApi = new ItellaPickupsApi();
    $response = $ItellaPickupsApi->request('pickups', $this->buildApiRequest());

    return $response ? $response : false;
  }

  public function buildApiRequest()
  {
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

  /**
   * $pickup = array(
   *  'sender' => 'Name / Company name',
   *  'address_1' => 'Street st. 1',
   *  'postcode' => '12345',
   *  'city' => 'City name',
   *  'country' => 'LT',
   *  'pickup_time' => '8:00 - 17:00',
   *  'contact_phone' => '+37060000000',
   * );
   */
  public function setPickUpAddress($pickupAddress)
  {
    $this->pickupAddress = array_merge($this->pickupAddress, $pickupAddress);
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
   *    'tracking_number' => '01234567890123456789',
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
}
