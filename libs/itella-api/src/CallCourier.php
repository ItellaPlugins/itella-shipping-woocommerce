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
    'address' => '',
    'pickup_time' => '',
    'contact_phone' => '',
  );
  private $subject = 'Call Itella Courier';
  private $items = array();

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
    $body = '<h2>Pickup information</h2><br/>' . PHP_EOL;
    $body .= '<table>' . PHP_EOL;
    if (!empty($this->pickupAddress['sender'])) {
      $body .= "<tr><td>Sender:</td><td>" . $this->pickupAddress['sender'] . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['address'])) {
      $body .= "<tr><td>Adress (pickup from):</td><td>" . $this->pickupAddress['address'] . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['contact_phone'])) {
      $body .= "<tr><td>Contact Phone:</td><td>" . $this->pickupAddress['contact_phone'] . "</td></tr>" . PHP_EOL;
    }
    if (!empty($this->pickupAddress['pickup_time'])) {
      $body .= "<tr><td>Pickup time:</td><td>" . $this->pickupAddress['pickup_time'] . "</td></tr>" . PHP_EOL;
    }
    $body .= '</table>' . PHP_EOL;
    if ($this->attachment) {
      $body .= "<br/>See attachment for manifest PDF." . PHP_EOL;
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
    $data['sender']['address'] = (!empty($this->pickupAddress['address'])) ? $this->pickupAddress['address'] : '';
    $data['sender']['phone'] = (!empty($this->pickupAddress['contact_phone'])) ? $this->pickupAddress['contact_phone'] : '';
    $data['pickup_time'] = (!empty($this->pickupAddress['pickup_time'])) ? $this->pickupAddress['pickup_time'] : '';
    $data['total_items'] = (!empty($this->items)) ? count($this->items) : 0;
    $data['items'] = (!empty($this->items)) ? $this->items : array();
    
    return $data;
  }

  /**
   * $pickup = array(
   *  'sender' => 'Name / Company name',
   *  'address' => 'Street, Postcode City, Country',
   *  'pickup_time' => '8:00 - 17:00',
   *  'contact_phone' => '865465411',
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
}
