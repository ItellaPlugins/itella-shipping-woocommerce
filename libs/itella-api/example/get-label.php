<?php
// TODO: write docs
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

require '../vendor/autoload.php';

use Mijora\Itella\ItellaException;
use \Mijora\Itella\Shipment\Shipment;

$track = $sample_track_nr;
// or if need multiple in one pdf
// $track = $sample_track_nr_array;

try {
  $shipment = new Shipment($p_user, $p_secret);
  $pdf_base64 = $shipment->downloadLabels($track);
  $pdf = base64_decode($pdf_base64);
  if ($pdf) { // check if its not empty
    if (is_array($track)) {
      $track = 'labels';
    }
    $path = dirname(__FILE__) . '/../temp/' . $track . '.pdf';
    $is_saved = file_put_contents($path, $pdf);
    $filename = 'labels.pdf';
    if (!$is_saved) { // make sure it was saved
      throw new ItellaException("Failed to save label pdf to: " . $path);
    }

    // make sure there is nothing before headers
    if (ob_get_level()) ob_end_clean();
    header("Content-Type: application/pdf; name=\"{$filename}\"");
    header("Content-Transfer-Encoding: binary");
    // disable caching on client and proxies, if the download content vary
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    readfile($path);
  } else {
    throw new ItellaException("Downloaded label data is empty.");
  }
} catch (ItellaException $e) {
  echo "Exception: <br>\n"
    . str_replace("\n", "<br>\n", $e->getMessage()) . "<br>\n";
}
