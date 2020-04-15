<?php

// TODO: TO BE REMOVED AS IT IS NO LONGER USED.


namespace Mijora\Itella\Pdf;

class Label
{
  private $shipment;

  private $out_base64 = false;
  private $out_string = false;

  private $defaultFont = array(
    'family' => 'helvetica',
    'style' => '',
    'size' => 6.5
  );

  private $addressFont = array(
    'family' => 'freeserif',
    'style' => '',
    'size' => 7
  );

  private $titleFont = array(
    'family' => 'helvetica',
    'style' => 'B',
    'size' => 5
  );

  private $barcodeStyle = array(
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => false,
    'cellfitalign' => '',
    'border' => false,
    'hpadding' => 'auto',
    'vpadding' => 0,
    'fgcolor' => array(0, 0, 0),
    'bgcolor' => false,
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 7,
    'stretchtext' => 0
  );

  private $pdf;
  private $margin = 5; // mm
  private $fillColor = array(220, 220, 200);
  private $painter = 'D'; // D - no fill, DF- fill

  public function __construct($shipment)
  {
    $this->shipment = $shipment;
    $this->pdf = new \setasign\Fpdi\Tcpdf\Fpdi(PDF_PAGE_ORIENTATION, 'mm', array(110, 190), true, 'UTF-8', false);

    $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $this->pdf->SetMargins($this->margin, 0, $this->margin, true);
    $this->pdf->setRTL(false, false);

    $this->pdf->SetHeaderMargin(false);
    $this->pdf->SetFooterMargin(false);

    $this->pdf->setPrintHeader(false);
    $this->pdf->setPrintFooter(false);

    $this->pdf->SetAutoPageBreak(false);

    // set image scale factor
    $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  }

  public function generateLabelPage($item, $packageNr = 1)
  {
    $printDate = date('Y.m.d');

    $this->pdf->AddPage('P', [106, 190]);

    // print a message
    $this->pdf->SetY(5);
    $this->pdf->SetFont('helvetica', 'B', 12);
    $posti = 'posti';
    $this->pdf->Cell(12, 0, $posti, 0, 0, 'L', false);
    $x = $this->pdf->GetX();
    $y = $this->pdf->GetY();
    $this->pdf->Ln();
    $x1 = $this->pdf->GetX();
    $y1 = $this->pdf->GetY();

    $this->pdf->SetXY($x, $y);
    $this->pdf->SetFontSize(7);
    $this->pdf->Cell(40, 0, 'Express Buisiness Day parcel 14', 0, 1, 'L', false);
    $this->pdf->SetY(5 + 5);
    $this->pdf->SetX(50);
    $this->pdf->SetFont('helvetica', 'B', 16.4);
    $this->pdf->Cell(10, 0, '14', 0, 1, 'C', false, '', 0, false, 'T', 'T');

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 7 + 5, 45, 15, 'CNZ');
    //content
    $this->pdf->Rect(5, 7 + 5, 45, 15, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetXY($x1, 7 + 5);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(20, 0, 'Sender', 0, 1, 'L', false);
    $this->setLabelFont($this->addressFont);
    $this->pdf->Cell(45, 2.5, $this->shipment->senderParty->name1, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->shipment->senderParty->street1, 0, 1, 'L', false);
    $post_city = $this->shipment->senderParty->postCode . ' ' . $this->shipment->senderParty->city;
    $this->pdf->Cell(45, 2.5, $post_city, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->getCountry($this->shipment->senderParty->countryCode), 0, 1, 'L', false);
    $this->pdf->StopTransform();

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 28, 45, 15, 'CNZ');
    //content
    $this->pdf->Rect(5, 28, 45, 15, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetY(28);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(20, 0, 'Recipient', 0, 0, 'L', false);
    $this->pdf->Cell(25, 0, $this->shipment->receiverParty->contactMobile, 0, 1, 'L', false);
    $this->setLabelFont($this->addressFont);
    $this->pdf->Cell(45, 2.5, $this->shipment->receiverParty->name1, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->shipment->receiverParty->street1, 0, 1, 'L', false);
    $this->setLabelFont($this->bold($this->addressFont));
    $post_city = $this->shipment->receiverParty->postCode . ' ' . $this->shipment->receiverParty->city;
    $this->pdf->Cell(45, 2.5, $post_city, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->getCountry($this->shipment->receiverParty->countryCode), 0, 1, 'L', false);
    $this->pdf->StopTransform();

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 43, 45, 7, 'CNZ');
    //content
    $this->pdf->Rect(5, 43, 45, 7, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->setLabelFont($this->addressFont);
    $this->pdf->SetY(43);
    $attn = 'Attn: ';
    $this->pdf->MultiCell(45, 7, $attn, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
    $this->pdf->StopTransform();

    // right side
    $barcode = '2W' . $item->productId;
    $this->pdf->SetY(5 + 5);
    $this->pdf->SetX(60 + $this->margin);
    $this->pdf->write1DBarcode($barcode, 'C128A', '', '', '', 12, 0.46, $this->barcodeStyle, 'N');
    $this->pdf->SetX(61);
    $this->pdf->SetFont('helvetica', 'B', 16.4);
    $this->pdf->Cell(0, 0, 'EDI', 0, 1, 'R', false);
    $this->pdf->SetFont('helvetica', '', 6.5);

    // Bounds
    $this->pdf->Rect(61, 29 + 5, 40, 7, $this->painter, array('TR' => 0, 'LB' => array('dash' => 0)), $this->fillColor);

    $this->pdf->SetY(29 + 5);
    $y = $this->pdf->GetY();

    $this->setLabelFont($this->titleFont);
    $this->pdf->SetX(61);
    $this->pdf->Cell(0, 7, 'Date', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetX(61);
    $this->pdf->Cell(0, 6, $printDate, 0, 1, 'L', false, '', 0, false, 'T', 'B');

    // Bounds
    $this->pdf->Rect(61, 41, 40, 7, $this->painter, array('R' => 0, 'LB' => array('dash' => 0)), $this->fillColor);
    $this->pdf->SetY(41);
    $this->pdf->SetX(61);
    $this->pdf->Cell(20, 6, $item->grossWeight . ' kg', 0, 0, 'R', false, '', 0, false, 'T', 'B');
    $this->pdf->Line($this->pdf->GetX(), 41, $this->pdf->GetX(), 48);
    $this->pdf->Cell(20, 6, $item->volume . ' m3', 0, 0, 'R', false, '', 0, false, 'T', 'B');

    // Additional services
    // Bounds
    $this->pdf->Rect(5, 51, 76, 6, $this->painter, array('TRB' => array('dash' => 0)), $this->fillColor);
    $this->pdf->SetY(51);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(25, 6, 'Aditional Services', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->Line($this->pdf->GetX(), 51, $this->pdf->GetX(), 57);
    $x = $this->pdf->GetX();
    $this->pdf->Cell(42, 6, 'Payer other than sender', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->SetX($x);
    $this->pdf->Line($x, 51, $x, 57);
    $this->pdf->Cell(42, 6, 'Contract number', 0, 0, 'L', false, '', 0, false, 'T', 'B');
    $x = $this->pdf->GetX();
    $this->pdf->Line($this->pdf->GetX(), 51, $this->pdf->GetX(), 57);
    $this->pdf->Cell(9, 6, 'Pcs', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->SetX($x);
    $this->setLabelFont($this->defaultFont);
    $this->pdf->Cell(9, 5, $packageNr . '/' . $item->packageQuantity, 0, 0, 'C', false, '', 0, false, 'T', 'B');

    // draw 5 service boxes
    for ($i = 1; $i <= 5; $i++) {
      $this->pdf->Rect(($i * 4 + 2), 53.5, 3.3, 3.3, 'D');
    }

    // Bounds
    $this->pdf->Rect(5, 57, $this->pdf->getPageWidth() - 10, 6, $this->painter, array('B' => array('dash' => 0)), $this->fillColor);
    $this->pdf->SetY(57);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->titleFont);
    $x = $this->pdf->GetX();
    $this->pdf->Cell(49, 6, 'Cash On Delivery sum', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetX($x);
    $this->pdf->Cell(49, 5, $this->shipment->codValue . ' EUR', 0, 0, 'R', false, '', 0, false, 'T', 'B');
    $this->pdf->Line($this->pdf->GetX(), 57, $this->pdf->GetX(), 63);
    $this->setLabelFont($this->titleFont);
    $x = $this->pdf->GetX();
    $this->pdf->Cell($this->pdf->getPageWidth() - 59, 6, 'IBAN', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetX($x);
    $this->pdf->Cell($this->pdf->getPageWidth() - 59, 5, $this->shipment->codIBAN, 0, 0, 'C', false, '', 0, false, 'T', 'B');

    // Bounds
    $this->pdf->Rect(5, 63, $this->pdf->getPageWidth() - 10, 6, $this->painter, array('B' => array('dash' => 0)), $this->fillColor);
    $this->pdf->SetY(63);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->titleFont);
    $x = $this->pdf->GetX();
    $this->pdf->Cell(72, 6, 'Bank Reference', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetX($x);
    $this->pdf->Cell(72, 5, '', 0, 0, 'R', false, '', 0, false, 'T', 'B');
    $this->pdf->Line($this->pdf->GetX(), 63, $this->pdf->GetX(), 69);
    $this->setLabelFont($this->titleFont);
    $x = $this->pdf->GetX();
    $this->pdf->Cell($this->pdf->getPageWidth() - 82, 6, 'BIC', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetX($x);
    $this->pdf->Cell($this->pdf->getPageWidth() - 82, 5, $this->shipment->codBIC, 0, 0, 'L', false, '', 0, false, 'T', 'B');

    // MAIN BARCODE

    $barcode = strtoupper($item->trackingNumber);
    $this->barcodeStyle2 = $this->barcodeStyle;
    $this->barcodeStyle2['text'] = false;
    $this->pdf->write1DBarcode($barcode, 'C128', 5, 71, '', 25, 0.46, $this->barcodeStyle2, 'N');
    $this->pdf->SetY(96);
    $barcodeFont = array_slice($this->defaultFont, 0, null, true);
    $barcodeFont['size'] = 7;
    $this->setLabelFont($barcodeFont);
    $this->pdf->Cell(0, 0, preg_replace('/(\w{4})(.{6})(\d{5})(\d{6})$/', '$1 $2 $3 $4', $barcode), 0, 0, 'C', false);

    // AFTER BARCODE
    // Notice of Arrival
    $this->pdf->SetY(100);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->bold($this->defaultFont));
    $this->pdf->Cell(50, 2.5, strtoupper('notice of arrival'), 0, 0, 'L', false);
    $this->pdf->SetY(103);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->titleFont);
    $this->pdf->MultiCell(50, 3.6, 'You can pick up the shipment at your post office. More information: www.posti.com', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'T', false);

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 108, 45, 6, 'CNZ');
    //content
    $this->pdf->Rect(5, 108, 45, 6, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetY(108);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(20, 0, 'Sender', 0, 1, 'L', false);
    $this->setLabelFont($this->addressFont);
    $this->pdf->Cell(45, 2.5, $this->shipment->senderParty->name1, 0, 1, 'L', false);
    $this->pdf->StopTransform();

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 115, 45, 15, 'CNZ');
    //content
    $this->pdf->Rect(5, 115, 45, 15, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetY(115);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(20, 0, 'Recipient', 0, 0, 'L', false);
    $this->pdf->Cell(25, 0, $this->shipment->receiverParty->contactMobile, 0, 1, 'L', false);
    $this->setLabelFont($this->addressFont);
    $this->pdf->Cell(45, 2.5, $this->shipment->receiverParty->name1, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->shipment->receiverParty->street1, 0, 1, 'L', false);
    $this->setLabelFont($this->bold($this->addressFont));
    $post_city = $this->shipment->receiverParty->postCode . ' ' . $this->shipment->receiverParty->city;
    $this->pdf->Cell(45, 2.5, $post_city, 0, 1, 'L', false);
    $this->pdf->Cell(45, 2.5, $this->getCountry($this->shipment->receiverParty->countryCode), 0, 1, 'L', false);
    $this->pdf->StopTransform();

    // Bounds
    $this->pdf->StartTransform();
    $this->pdf->Rect(5, 130, 45, 7, 'CNZ');
    //content
    $this->pdf->Rect(5, 130, 45, 7, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetY(130);
    $this->setLabelFont($this->addressFont);
    $attn = 'Attn: ';
    $this->pdf->MultiCell(45, 7, $attn, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
    $this->pdf->StopTransform();

    //INFO
    $this->pdf->SetY(100);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->bold($this->defaultFont));
    $x = 60;
    $width = $this->pdf->getPageWidth() - $x - 5;
    $this->pdf->SetX($x);
    $this->pdf->Cell($width, 2.5, 'Express Buisness Day parcel 14', 0, 0, 'R', false);
    $this->pdf->SetY(103);
    $this->pdf->Ln(0);
    $this->setLabelFont($this->titleFont);
    $this->pdf->SetX($x);
    $this->pdf->Cell($width, 3.6, 'Additional Services', 0, 0, 'L', false, '', 0, false, 'T', 'B');

    // Bounds
    $this->pdf->Rect($x, 107, $width, 10, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetXY($x, 107);
    if ($item->hasExtraService('3101')) {
      $this->pdf->Cell($width / 2, 3, 'C.O.D', 0, 0, 'L', false);
    }
    if ($item->hasExtraService('3104')) {
      $this->pdf->Cell($width / 2, 3, 'Fragile', 0, 0, 'L', false);
    }
    $this->pdf->SetXY($x, 110);
    if ($item->hasExtraService('3174')) {
      $this->pdf->Cell($width / 2, 3, 'Oversized', 0, 0, 'L', false);
    }
    if ($item->hasExtraService('3102')) {
      $this->pdf->Cell($width / 2, 3, 'Multiparcel', 0, 0, 'L', false);
    }
    $this->pdf->SetXY($x, 113);
    if ($item->hasExtraService('3166')) {
      $this->pdf->Cell($width, 3, 'Call before delivery', 0, 0, 'L', false);
    }

    // Bounds
    $this->pdf->Rect($x, 118, $width, 25, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->pdf->SetXY($x, 118);
    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell($width, 2.5, 'Contents', 0, 0, 'L', false, '', 0, false, 'T', 'B');
    $this->pdf->SetXY($x, 121);
    $this->pdf->Line($x, $this->pdf->GetY(), $x + $width, $this->pdf->GetY());

    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(10, 5, 'Pcs', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->GetX(), $this->pdf->GetY() + 5);
    $this->pdf->Cell(17, 5, '', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->GetX(), $this->pdf->GetY() + 5);
    $this->pdf->Cell($width - 27, 5, 'Date', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetXY($x, 121);
    $this->pdf->Cell(10, 5, $packageNr . '/' . $item->packageQuantity, 0, 0, 'C', false, '', 0, false, 'T', 'B');
    $this->pdf->Cell(17, 5, $item->grossWeight . ' kg', 0, 0, 'R', false, '', 0, false, 'T', 'B');
    $this->pdf->Cell($width - 27, 5, $printDate, 0, 0, 'L', false, '', 0, false, 'T', 'B');
    $this->pdf->SetXY($x, 126);
    $this->pdf->Line($x, $this->pdf->GetY(), $x + $width, $this->pdf->GetY());

    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell(24, 5, 'Cash On Delivery sum', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), $this->pdf->GetX(), $this->pdf->GetY() + 5);
    $this->pdf->Cell($width - 24, 5, 'BIC', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetXY($x, 126);
    $this->pdf->Cell(24, 5, $this->shipment->codValue . ' EUR', 0, 0, 'R', false, '', 0, false, 'T', 'B');
    $this->pdf->Cell($width - 24, 5, $this->shipment->codBIC, 0, 0, 'L', false, '', 0, false, 'T', 'B');
    $this->pdf->SetXY($x, 131);
    $this->pdf->Line($x, $this->pdf->GetY(), $x + $width, $this->pdf->GetY());

    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell($width, 5, 'IBAN', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetXY($x, 131);
    $this->pdf->Cell($width, 5, $this->shipment->codIBAN, 0, 0, 'L', false, '', 0, false, 'T', 'B');
    $this->pdf->SetXY($x, 136);
    $this->pdf->Line($x, $this->pdf->GetY(), $x + $width, $this->pdf->GetY());

    $this->setLabelFont($this->titleFont);
    $this->pdf->Cell($width, 5, 'Bank reference', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->defaultFont);
    $this->pdf->SetXY($x, 136);
    $this->pdf->Cell($width, 5, '', 0, 0, 'L', false, '', 0, false, 'T', 'B');

    $this->pdf->SetXY(5, 153);
    $this->pdf->Line(5, $this->pdf->GetY(), $this->pdf->getPageWidth() - 5, $this->pdf->GetY(), array('width' => 0.5));
    $this->setLabelFont($this->titleFont);
    $this->pdf->SetXY(5, 154);
    $width = ($this->pdf->getPageWidth() - 10) / 2;
    $this->pdf->Cell($width, 2.5, 'Recipient signature', 0, 0, 'L', false, '', 0, false, 'T', 'T');
    $this->pdf->Cell($width, 2.5, 'time', 0, 0, 'L', false, '', 0, false, 'T', 'T');

    // Bounds
    $width = $this->pdf->getPageWidth() - 10;
    $this->pdf->Rect(5, 158, $width, 20, $this->painter, array('LTRB' => 0), $this->fillColor);
    $this->setLabelFont($this->titleFont);
    $this->pdf->SetXY(5, 158);
    $this->pdf->Cell($width, 2.5, 'Additional Services', 0, 1, 'L', false, '', 0, false, 'T', 'T');
    $this->setLabelFont($this->bold($this->defaultFont));
    if ($item->hasExtraService('3101')) {
      $this->pdf->Cell($width, 3, strtoupper('3101 COD (Cash On Delivery)'), 0, 1, 'L', false, '', 0, false, 'T', 'T');
    }
    if ($item->hasExtraService('3102')) {
      $this->pdf->Cell($width, 3, strtoupper('3102 Multiparcel'), 0, 1, 'L', false, '', 0, false, 'T', 'T');
    }
    if ($item->hasExtraService('3104')) {
      $this->pdf->Cell($width, 3, strtoupper('3104 Fragile'), 0, 1, 'L', false, '', 0, false, 'T', 'T');
    }
    if ($item->hasExtraService('3166')) {
      $this->pdf->Cell($width, 3, strtoupper('3166 Call before Delivery'), 0, 1, 'L', false, '', 0, false, 'T', 'T');
    }
    if ($item->hasExtraService('3174')) {
      $this->pdf->Cell($width, 3, strtoupper('3174 Oversized'), 0, 1, 'L', false, '', 0, false, 'T', 'T');
    }
  }

  public function printLabel($fileName, $path = null)
  {
    foreach ($this->shipment->goodsItems as $key => $item) {
      $this->generateLabelPage($item, $key + 1);
    }

    // reset pointer to the last page
    $this->pdf->lastPage();

    // ---------------------------------------------------------
    //Close and output PDF document
    if ($this->out_string) {
      $pdf_string = $this->pdf->Output($fileName, 'S');
      return $this->out_base64 ? base64_encode($pdf_string) : $pdf_string;
    }
    if ($path == null) {
      $this->pdf->Output($fileName, 'I');
      return true;
    } else {
      if (file_exists($path . $fileName)) {
        unlink($path . $fileName);
      }
      $this->pdf->Output($path . $fileName, 'F');
      return true;
    }

    return false;
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

  private function setLabelFont($style)
  {
    $this->pdf->SetFont($style['family'], $style['style'], $style['size']);
  }

  private function bold($style)
  {
    return array('family' => $style['family'], 'style' => 'B', 'size' => $style['size']);
  }

  private function getCountry($countryCode)
  {
    $countries = array(
      'lt' => 'Lithuania',
      'lv' => 'Latvia',
      'ee' => 'Estonia',
      'fi' => 'Finland'
    );
    $countryCode = strtolower($countryCode);
    return isset($countries[$countryCode]) ? $countries[$countryCode] : '';
  }
}
