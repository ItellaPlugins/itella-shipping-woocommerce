<?php
// TODO: write docs
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use Mijora\Itella\ItellaException;
use Mijora\Itella\Shipment\Shipment;
use Mijora\Itella\Pdf\PDFMerge;

try {
  $pdf = array();
  $filename = time(); // temp filename
  // normaly tracking numbers should be sorted in two arrays (2711 and 2317 products)
  // but for this example simply call downloadLabels for each tracking number in example
  foreach($sample_track_nr_array as $key => $nr) {
    $shipment = new Shipment($p_user, $p_secret); // user and pass will depend if its for 2711 or 2317 product
    $result = base64_decode($shipment->downloadLabels($nr));
    if ($result) { // check if its not empty and save temporary for merging
      $path = dirname(__FILE__) . '/../temp/' . $filename . '-' . $key . '.pdf';
      file_put_contents($path, $result);
      $pdf[] = $path;
    }
  }

  $merger = new PDFMerge();
  $merger->setFiles($pdf); // pass array of paths to pdf files
  $merger->merge();
  foreach($pdf as $file) { // remove temp files
    if (is_file($file)) {
      unlink($file);
    }
  }
  /**
   * Second param:
   * I: send the file inline to the browser (default).
   * D: send to the browser and force a file download with the name given by name.
   * F: save to a local server file with the name given by name.
   * S: return the document as a string (name is ignored).
   * FI: equivalent to F + I option
   * FD: equivalent to F + D option
   * E: return the document as base64 mime multi-part email attachment (RFC 2045)
   */
  $merger->Output('labels.pdf','I');
} catch (ItellaException $e) {
  echo "Exception: <br>\n"
    . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n";
}
