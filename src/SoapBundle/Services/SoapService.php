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
  public function __construct(UserAuthInterface $userAuthInterface) {
    $this->userAuthInterface = $userAuthInterface;
  }

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
    // @TODO: log this soap call.

    $strUserName = $request->strUserName;
    $strPassword = $request->strPassword;

    // Initial "fail" response.
    $result = array(session_id(), 'nvu');

    // If the service isn't set for whatever reason we can't continue.
    if (!isset($this->userAuthInterface)) {
      // @TODO: log "Unable to authenticate user" message.

      $result = array(session_id(), 'nvu');
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
