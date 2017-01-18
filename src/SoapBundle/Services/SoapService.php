<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;
use Drupal\user\UserAuthInterface;

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
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  private $userAuthInterface;

  /**
   * The QBXML parser service.
   *
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\QBXMLParser
   */
  private $qbxmlParser;

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
   * The UID of the logged-in SOAP client.
   *
   * @var int
   */
  protected $soapUserID;

  /**
   * SoapService constructor.
   *
   * @param \Drupal\user\UserAuthInterface $userAuthInterface
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\QBXMLParser $parser
   */
  public function __construct(UserAuthInterface $userAuthInterface, QBXMLParser $parser) {
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser = $parser;
  }

  /**
   * Builds a response to WebConnect out of the incoming raw request.
   *
   * A response to Quickbooks is expected to be formatted as a stdClass object
   * with a property named [methodName]Result that contains an array/string
   * formatted to the QBWC specs.
   *
   * @param $method
   *   The wsdl call being invoked.
   * @param $data
   *   The SOAP request
   *
   * @return string
   *   The xml string to return back to the client by the SOAP server.
   */
  public function __call($method, $data) {
    // @TODO: log the incoming SOAP call.

    $callback = "call_$method";

    $response = NULL;

    // If a valid method method is being called, parse the incoming request
    // and call the method with the parsed data passed in.
    if (is_callable($callback)) {
      // Prepare the response to the client.
      $request = $this->prepareResponse($method, $data);
      $response = $callback($request);
    }

    return $response;
  }

  /**
   * Builds the stdClass object required by a service response handler.
   *
   * @param string $method_name
   *   The Quikcbooks method being called.
   * @param string $data
   *   The raw incoming soap request.
   *
   * @return \stdClass
   *   An object with the following properties:
   *   stdClass {
   *     methodNameResult => '',
   *     requestParam1 => 'foo',
   *     ...
   *     requestParamN => 'bar',
   *   }
   */
  private function prepareResponse($method_name, $data) {
    $response = new \stdClass;
    $response->$method_name = '';

    simplexml_load_string($data);

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function call_serverVersion(\stdClass $request) {
    $request->serverVersionResult = $this->serverVersion;
    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function call_clientVersion(\stdClass $request) {
    $this->clientVersion = $request->strVersion;

    $request->clientVersionResult = '';
    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function call_authenticate(\stdClass $request) {
    $strUserName = $request->strUserName;
    $strPassword = $request->strPassword;

    // Initial "fail" response.
    $result = array(session_id(), 'nvu');

    // If the service isn't set for whatever reason we can't continue.
    if (!isset($this->userAuthInterface)) {
      // @TODO: log "Unable to authenticate user" message.
    }
    else {
      $uid = $this->userAuthInterface->authenticate($strUserName, $strPassword);

      if ($uid === FALSE) {
        // @TODO: log "Invalid login" message.
      }
      else {
        // @TODO: log "User $strUserName successfully authenticated" message.

        $this->soapUserID = $uid;
        $result = array($strUserName . "|" . $strPassword, '');
      }
    }

    $request->authenticateResult = $result;
    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function call_sendRequestXML(\stdClass $request) {
    // TODO: Implement sendRequestXML() method.
  }

  /**
   * {@inheritDoc}
   */
  public function call_receiveResponseXML(\stdClass $request) {
    // TODO: Implement receiveResponseXML() method.
  }

  /**
   * {@inheritDoc}
   */
  public function call_getLastError(\stdClass $request) {
    // TODO: Implement getLastError() method.
  }

  /**
   * {@inheritDoc}
   */
  public function call_closeConnection(\stdClass $request) {
    // TODO: Implement closeConnection() method.
  }
}
