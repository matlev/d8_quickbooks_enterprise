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
   */
  public function __construct() {}

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
   * Builds the SOAP server response.
   *
   * @param string $type
   *   The type of response XML WebConnect is expecting.  The accepted values
   *   are set in the baseFieldDefinition of the
   *   commerce_quickbooks_enterprise_qbitem entity.
   *
   * @see Drupal\commerce_quickbooks_enterprise\Entity\QBItem::baseFieldDefinitions()
   *
   * @param \stdClass $properties
   *   A basic object containing keyed values for the QBXML template.
   *
   * @return QBXMLParser
   *   The parser for chaining calls.
   */
  public function buildResponseXML($type, \stdClass $properties) {
    // Retrieve the valid types of QB Items we're allowed to export
    $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions("commerce_quickbooks_enterprise_qbitem");
    $field_definition = $bundle_fields['item_type'];
    $valid_types = $field_definition->getSetting('allowed_values');

    // Return an empty response if the $type isn't valid
    if (!in_array($type, $valid_types)) {
      $this->responseXML = null;
      return $this;
    }

    // Call the appropriate QBXML template.
    $qbxml_theme = array(
      '#theme' => $type . '_qbxml',
      '#properties' => $properties,
    );
    $qbxml = \Drupal::service('renderer')->render($qbxml_theme, FALSE);

    // If something went wrong during the render, return a null result.
    if (empty($qbxml)) {
      $this->responseXML = null;
      return $this;
    }

    $this->responseXML = $qbxml;
    return $this;
  }

  /**
   * Get the XML response for WebConnect.
   *
   * @return string
   */
  public function getResponseXML() {
    return $this->responseXML;
  }
}
