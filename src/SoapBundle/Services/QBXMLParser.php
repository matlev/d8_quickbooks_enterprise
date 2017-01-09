<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

class QBXMLParser {

  public $xmlBody;

  public function __construct($xml) {
    $this->xmlBody = $xml;
  }

  public function setXML($xml) {
    $this->xmlBody = $xml;
  }

  public function getXml($xml) {
    return $this->xmlBody;
  }


  public function getXMLAsObject($xml = null) {
    $xml = $xml || $this->xmlBody;

    $xml_object = new \stdClass;

    // Convert to object

    return $xml_object;
  }

}