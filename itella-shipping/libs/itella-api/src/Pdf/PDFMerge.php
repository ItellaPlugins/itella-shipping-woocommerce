<?php

namespace Mijora\Itella\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;

class PDFMerge extends Fpdi
{
  public $files = array();

  public function setFiles($files)
  {
    $this->files = $files;
  }

  public function merge()
  {
    $this->setPrintHeader(false);
		$this->setPrintFooter(false);
    foreach ($this->files as $file) {
      $pageCount = $this->setSourceFile($file);
      for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pageId = $this->ImportPage($pageNo);
        $s = $this->getTemplatesize($pageId);
        $this->AddPage($s['orientation'], $s);
        $this->useImportedPage($pageId);
      }
    }
  }
}
