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
   * The QBXML string to return as a response to WebConnect.
   *
   * @var string
   */
  protected $responseXML;

  /**
   * The parsed XML.
   *
   * @var \SimpleXMLElement
   */
  protected $simpleXMLObject;

  /**
   * An array of errors returned in a Quickbooks response.
   *
   * @var array
   */
  protected $errors;

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
   * @return \SimpleXMLElement
   *   Returns a SimpleXMLElement object
   */
  public function getSimpleXMLObject($xml = null) {
    $xml = $xml || $this->requestXML;
    $parsed = new \SimpleXMLElement($xml);

    $this->simpleXMLObject = $parsed;

    return $parsed;
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
    $bundle_fields = \Drupal::getContainer()
      ->get('entity_field.manager')
      ->getFieldDefinitions("commerce_quickbooks_enterprise_qbitem");
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
   * Parse out the error messages returned from Quickbooks, if any.
   */
  public function parseQuickbooksErrors() {
    if (empty($this->requestXML)) {
      return;
    }

    // Parse the xml if it hasn't been done so already.
    if (empty($this->simpleXMLObject)) {
      $this->getSimpleXMLObject();
    }

    if ($this->simpleXMLObject instanceof \SimpleXMLElement) {
      $errors = [];
      $response_elements = $this->simpleXMLObject->QBXMLMsgsRs->children();

      foreach ($response_elements as $name => $element) {
        $attributes = $element->attributes();

        if (isset($attributes->statusSeverity) && isset($attributes->statusMessage) && $attributes->statusSeverity == 'Error') {
          $errors[] = array(
            "statusCode" => (string) $attributes->statusCode,
            "statusMessage" => (string) $attributes->statusMessage
          );
        }
      }

      $this->errors = $errors;
    }
  }

  /**
   * Get the XML response for WebConnect.
   *
   * @return string
   */
  public function getResponseXML() {
    return $this->responseXML;
  }

  /**
   * Get the list of errors parsed from a Quickbooks response.
   *
   * @return array
   */
  public function getErrorList() {
    return $this->errors;
  }
}
