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
   * Process the incoming request and call the appropriate service method.
   *
   * This magic function is responsible for processing the incoming SOAP request
   * and doing user session validation if required for the incoming service call.
   *
   * A response to Quickbooks is expected to be formatted as a stdClass object
   * with a property named [methodName]Result that contains an array/string
   * formatted to the QBWC specs.
   *
   * @param $method
   *   The wsdl call being invoked.
   * @param $data
   *   The SOAP request object.
   *
   * @return \stdClass
   *   The response object expected by Quickbooks.
   */
  public function __call($method, $data);

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
   * Requires session validation.
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
   * Requires session validation.
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
   * Requires session validation.
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
   * Requires session validation.
   *
   * @param \stdClass $request
   *   $request->ticket
   *   $request->closeConnectionResult
   *
   * @return \stdClass
   */
  public function call_closeConnection(\stdClass $request);
}