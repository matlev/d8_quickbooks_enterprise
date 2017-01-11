<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

class QBXMLParser {

  /**
   * The XML from the incoming SOAP request.
   *
   * @var string
   */
  public $requestXML;

  /**
   * The QBXML string to return as a response to WebConnect
   *
   * @var string
   */
  protected $responseXML;

  /**
   * QBXMLParser constructor.
   *
   * @param $xml
   *   Optionally set the incoming request XML on construction.
   */
  public function __construct($xml) {
    $this->requestXML = $xml;
  }

  /**
   * Set the SOAP request XML.
   *
   * @param $xml string
   *
   * @return QBXMLParser
   *   The parser for chaining calls.
   */
  public function setRequestXML($xml) {
    $this->requestXML = $xml;
    return $this;
  }

  /**
   * Return the SOAP request XML
   *
   * @return string
   *   The
   */
  public function getRequestXml() {
    return $this->requestXML;
  }

  /**
   * Retrieve an object representation of the SOAP request XML.
   *
   * Will attempt to use user input XML first, and default to $this->requestXML.
   *
   * @param null $xml
   *   An optional XML string.
   *
   * @return \stdClass | boolean
   *   Returns a stdClass object, or FALSE if the XML was unsuccessfully parsed.
   */
  public function getXMLAsObject($xml = null) {
    $xml = $xml || $this->requestXML;

    $xml_object = new \stdClass;

    // Convert to object

    return $xml_object;
  }

  /**
   *
   */
  public function buildResponseXML($type) {
    // Retrieve the valid types of QB Items we're allowed to export
    $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions("commerce_quickbooks_enterprise_qbitem");
    $field_definition = $bundle_fields['item_type'];
    $valid_types = $field_definition->getSetting('allowed_values');

    // Return an empty response if the $type isn't valid
    if (!in_array($type, $valid_types)) {
      $this->responseXML = null;
      return;
    }



  }

}