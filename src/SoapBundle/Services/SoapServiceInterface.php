<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

/**
 * A Quickbooks SOAP Service must implement these functions.
 *
 * Interface SoapServiceInterface
 * @package Drupal\commerce_quickbooks_enterprise\SoapBundle\Services
 */
interface SoapServiceInterface {

  /**
   * Send the server version to the client
   *
   * @param \stdClass $request
   *   $request->serverVersionResult
   *
   * @return \stdClass
   */
  public function serverVersion(\stdClass $request);

  /**
   * Check client version
   *
   * @param \stdClass $request
   *   $request->strVersion
   *   $request->clientVersionResult
   *
   * @return \stdClass
   */
  public function clientVersion(\stdClass $request);

  /**
   * Authenticate and initiate session with client
   *
   * @param \stdClass $request
   *   $request->strUserName
   *   $request->strPassword
   *   $request->authenticationResult
   *
   * @return \stdClass
   */
  public function authenticate(\stdClass $request);

  /**
   * Send data back to client
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->sendRequestXMLResult
   *
   * @return \stdClass
   */
  public function sendRequestXML(\stdClass $request);

  /**
   * Get response from last quickbooks operation
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->receiveResponseXMLResult
   *
   * @return \stdClass
   */
  public function receiveResponseXML(\stdClass $request);

  /**
   * Quickbooks error handler
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->getLastErrorResult
   *
   * @return \stdClass
   */
  public function getLastError(\stdClass $request);

  /**
   * Close the connection
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->closeConnectionResult
   *
   * @return \stdClass
   */
  public function closeConnection(\stdClass $request);
}