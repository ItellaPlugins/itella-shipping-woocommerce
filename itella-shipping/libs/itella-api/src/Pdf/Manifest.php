<?php
// TODO: Write docs
namespace Mijora\Itella\Pdf;

class Manifest
{
  public $senderName;
  public $senderLastname;
  public $senderAddress;
  public $senderPostCode;
  public $senderCity;
  public $senderCountry;

  public $timestamp;
  public $dateFormat = 'Y-m-d';

  private $items = array();

  private $strings = array(
    'sender_address' => 'Sender address:',
    'nr' => 'Nr.',
    'track_num' => 'Tracking number',
    'date' => 'Date',
    'amount' => 'Amount',
    'weight' => 'Weight (kg)',
    'delivery_address' => 'Delivery address',
    'courier' => 'Courier',
    'sender' => 'Sender',
    'name_lastname_signature' => 'name, lastname, signature',
  );

  private $out_base64 = false;
  private $out_string = false;

  private $pdf;

  /**
   * @param int $timestamp unix timestamp, if left false will be assigned current system time
   * @param string $dateFormat Date format string can be anything that php date() supports. Default: Y-m-d
   * 
   * @return void
   */
  public function __construct($timestamp = false, $dateFormat = 'Y-m-d')
  {
    $this->timestamp = $timestamp;
    if (!$timestamp || !is_int($timestamp)) {
      $this->timestamp = time();
    }

    $this->dateFormat = $dateFormat;

    $this->pdf = new \setasign\Fpdi\Tcpdf\Fpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $this->pdf->setPrintHeader(false);
    $this->pdf->setPrintFooter(false);
  }

  /**
   * 
   * Adds one or more items to be added to manifest.
   * 
   * $item = array(
   *  'track_num' => $value,
   *  'weight' => $value,
   *  'delivery_address' => $value,
   * );
   * 
   * @param array|array([]) $item one $item or array of $items
   */
  public function addItem($item)
  {
    // ignore item if its not array
    if (!is_array($item)) {
      return $this;
    }

    if (isset($item['track_num'])) {
      $item = array($item);
    }

    $this->items = array_merge($this->items, $item);
    return $this;
  }

  public function setSenderName($senderName)
  {
    $this->senderName = $senderName;
    return $this;
  }

  /**
   * optional
   */
  public function setSenderLastname($senderLastname = '')
  {
    $this->senderLastname = $senderLastname;
    return $this;
  }

  public function setSenderAddress($senderAddress)
  {
    $this->senderAddress = $senderAddress;
    return $this;
  }

  public function setSenderPostCode($senderPostCode)
  {
    $this->senderPostCode = $senderPostCode;
    return $this;
  }

  public function setSenderCity($senderCity)
  {
    $this->senderCity = $senderCity;
    return $this;
  }

  public function setSenderCountry($senderCountry)
  {
    $this->senderCountry = $senderCountry;
    return $this;
  }

  public function setStrings($new_strings)
  {
    if (!is_array($new_strings)) {
      return $this;
    }

    $this->strings = array_merge($this->strings, $new_strings);
    return $this;
  }

  public function setBase64($isOn = false)
  {
    $this->out_base64 = $isOn;
    return $this;
  }

  public function setToString($isOn = false)
  {
    $this->out_string = $isOn;
    return $this;
  }

  public function printManifest($fileName, $path = null)
  {
    $this->pdf->AddPage();
    $order_table = '';
    $count = 1;

    foreach ($this->items as $item) {
      $order_table .= '<tr>
          <td width = "40" align="right">' . $count . '.</td>
          <td width="100">' . $item['track_num'] . '</td>
          <td width = "60">' . date('Y-m-d', $this->timestamp) . '</td>
          <td width = "40">1</td>
          <td width = "60">' . $item['weight'] . '</td>
          <td width = "210">' . $item['delivery_address'] . '</td>
          </tr>';
      $count++;
    }
    // add itella logo
    $image = dirname(__FILE__) . DIRECTORY_SEPARATOR . '/logo.png';
    $this->pdf->Image($image, 10, 20, 50);
    
    $this->pdf->SetFont('freeserif', '', 14);
    $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date($this->dateFormat, $this->timestamp)
      . '</td><td>' . $this->strings['sender_address'] . '<br/>' . $this->senderName . '<br/>'
      . $this->senderAddress . ',<br/>'
      . $this->senderPostCode . ' '
      . $this->senderCity . ', '
      . $this->senderCountry
      . '<br/></td></tr></table>';

    $this->pdf->writeHTML($shop_addr, true, false, false, false, '');
    $tbl = '
        <table cellspacing="0" cellpadding="4" border="1">
          <thead>
            <tr>
              <th width = "40" align="right" >' . $this->strings['nr'] . '</th>
              <th width="100">' . $this->strings['track_num'] . '</th>
              <th width = "60">' . $this->strings['date'] . '</th>
              <th width = "40" >' . $this->strings['amount'] . '</th>
              <th width = "60" >' . $this->strings['weight'] . '</th>
              <th width = "210" >' . $this->strings['delivery_address'] . '</th>
            </tr>
          </thead>
          <tbody>
            ' . $order_table . '
          </tbody>
        </table><br/><br/>';

    $this->pdf->SetFont('freeserif', '', 9);
    $this->pdf->writeHTML($tbl, true, false, false, false, '');
    $this->pdf->SetFont('freeserif', '', 14);
    $sign = $this->strings['courier'] . ' ' . $this->strings['name_lastname_signature'] . ' ________________________________________________<br/><br/>';
    $sign .= $this->strings['sender'] . ' ' . $this->strings['name_lastname_signature'] . ' ________________________________________________';
    $this->pdf->writeHTML($sign, true, false, false, false, '');

    if ($this->out_string) {
      $this->pdf_string = $this->pdf->Output($fileName, 'S');
      return $this->out_base64 ? base64_encode($this->pdf_string) : $this->pdf_string;
    }

    if ($path == null) {
      $this->pdf->Output($fileName, 'I');
    } else {
      if (file_exists($path . $fileName)) {
        unlink($path . $fileName);
      }
      $this->pdf->Output($path . $fileName, 'F');
    }
  }
}
