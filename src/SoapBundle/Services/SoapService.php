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
   */
  public function __construct(UserAuthInterface $userAuthInterface, QBXMLParser $parser) {
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser= $parser;
  }

  /**
   * Magic function to convert SOAP XMl to a stdClass object.
   *
   * @param $method
   *   The wsdl call being invoked.
   *
   * @param $args
   *   The SOAP request
   *
   * @return string
   *   The xml string to return back to the client by the SOAP server.
   */
  public function __call($method, $args) {
    // @TODO: log the incoming SOAP call.

    $callback = "call_$method";

    // Prepare the request from the client.
    $request = new \stdClass();

    if (is_callable($callback)) {
      $xml_obj = simplexml_load_string($args);
    }

    $response = $this->prepareResponse($request);

    return $response;
  }

  private function prepareResponse(\stdClass $data) {
    $xml_response = "";

    return $xml_response;
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
