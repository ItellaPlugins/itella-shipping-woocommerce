<?php
// TODO: To be decided if library should handle this or not as pakettikauppa does not have this functionality at the moment
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;
class CallCourier
{
  private $itella_email;
  private $sender_email;
  private $isTest = false;
  private $pickupAddress = array(
    'sender' => '',
    'address' => '',
    'pickup_time' => '',
    'contact_phone' => '',
  );
  private $subject = 'Call Itella Courier';

  public function __construct($itella_email, $isTest = false)
  {
    $this->itella_email = $itella_email;
    $this->isTest = $isTest;
  }

  /**
   * Sends email using mail() even if successfull does not mean mail will reach recipient
   * 
   * @throws Exception when mail fails to register for sending
   */
  public function callCourier()
  {
    // Force PHP to use the UTF-8 charset
    header('Content-Type: text/html; charset=utf-8');

    // Define and Base64 encode the subject line
    $subject_text = ($this->isTest ? 'TEST CALL - ' : '') . $this->subject;
    $subject = '=?UTF-8?B?' . base64_encode($subject_text) . '?=';

    // Add custom headers
    $headers = 'Content-Type: text/plain; charset=utf-8' . "\r\n";
    $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";
    $headers .= 'From: ' . $this->sender_email . "\r\n";

    // Base64 the email body text
    $headers .= rtrim(chunk_split(base64_encode($this->buildMailBody())));
    // Send mail with custom headers
    if (!mail($this->itella_email, $subject, '', $headers)) {
      throw new ItellaException('Oops, something gone wrong!');
    }

    return true;
  }

  public function buildMailBody()
  {
    $body =
      ($this->isTest ? "TEST CALL\r\n\r\n" : "") .
      "Sender: " . $this->pickupAddress['sender'] . "\r\n" .
      "Adress: " . $this->pickupAddress['address'] . "\r\n" .
      "Contact Phone: " . $this->pickupAddress['contact_phone'] . "\r\n" .
      "Pickup time: " . $this->pickupAddress['pickup_time'] . "\r\n";
    return $body;
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
}
