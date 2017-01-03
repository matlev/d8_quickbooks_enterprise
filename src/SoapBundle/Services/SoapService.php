<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

/**
 * Handle SOAP requests and return a response.
 *
 * @TODO: Implement logging for each request.
 *
 * Class SoapService
 * @package Drupal\commerce_quickbooks_enterprise\SoapBundle\Services
 */
class SoapService implements SoapServiceInterface {

  /**
   * The current server version.
   *
   * @var string
   */
  private $serverVersion = '1.0';

  /**
   * The version returned by the client.
   *
   * @var string
   */
  protected $clientVersion;

  /**
   * {@inheritDoc}
   */
  public function serverVersion(\stdClass $request) {
    // @TODO: log this soap call.

    $request->serverVersionResult = $this->serverVersion;
    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function clientVersion(\stdClass $request) {
    // @TODO: log this soap call.

    $this->clientVersion = $request->strVersion;

    $request->clientVersionResult = '';
    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function authenticate(\stdClass $request) {
    // TODO: Implement authenticate() method.
  }

  /**
   * {@inheritDoc}
   */
  public function sendRequestXML(\stdClass $request) {
    // TODO: Implement sendRequestXML() method.
  }

  /**
   * {@inheritDoc}
   */
  public function receiveResponseXML(\stdClass $request) {
    // TODO: Implement receiveResponseXML() method.
  }

  /**
   * {@inheritDoc}
   */
  public function getLastError(\stdClass $request) {
    // TODO: Implement getLastError() method.
  }

  /**
   * {@inheritDoc}
   */
  public function closeConnection(\stdClass $request) {
    // TODO: Implement closeConnection() method.
  }
}
