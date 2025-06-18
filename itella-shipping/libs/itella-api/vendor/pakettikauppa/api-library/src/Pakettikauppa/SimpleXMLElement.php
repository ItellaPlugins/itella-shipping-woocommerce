<?php

namespace Pakettikauppa;

class SimpleXMLElement extends \SimpleXMLElement
{
  /**
   * Escapes input text
   *
   * @param string
   * @param string|null
   * @param string|null
   */
  public function addChild($qualifiedName, $value = null, $namespace = null)
  {
    if ( $value != null )
    {
      $value = htmlspecialchars($value, ENT_XML1);
    }

    return parent::addChild($qualifiedName, $value, $namespace);
  }
}
