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
  public function call_serverVersion(\stdClass $request);

  /**
   * Check client version
   *
   * @param \stdClass $request
   *   $request->strVersion
   *   $request->clientVersionResult
   *
   * @return \stdClass
   */
  public function call_clientVersion(\stdClass $request);

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
  public function call_authenticate(\stdClass $request);

  /**
   * Send data back to client
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->sendRequestXMLResult
   *
   * @return \stdClass
   */
  public function call_sendRequestXML(\stdClass $request);

  /**
   * Get response from last quickbooks operation
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->receiveResponseXMLResult
   *
   * @return \stdClass
   */
  public function call_receiveResponseXML(\stdClass $request);

  /**
   * Quickbooks error handler
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->getLastErrorResult
   *
   * @return \stdClass
   */
  public function call_getLastError(\stdClass $request);

  /**
   * Close the connection
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->closeConnectionResult
   *
   * @return \stdClass
   */
  public function call_closeConnection(\stdClass $request);
}