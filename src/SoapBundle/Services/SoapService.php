<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;

/**
 * Handle SOAP requests and return a response.
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
   * The session manager.
   *
   * Responsible for managing, validating and invalidating SOAP sessions.
   *
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapSessionManager
   */
  private $session_manager;

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
   * SoapService constructor.
   *
   * @param \Drupal\user\UserAuthInterface $userAuthInterface
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\QBXMLParser $parser
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapSessionManager $sessionManager
   */
  public function __construct(UserAuthInterface $userAuthInterface, QBXMLParser $parser, SoapSessionManager $sessionManager) {
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser = $parser;
    $this->session_manager = $sessionManager;
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
   *   The SOAP request object.
   *
   * @return \stdClass
   *   The response object expected by Quickbooks.
   */
  public function __call($method, $data) {
    \Drupal::logger('commerce_qbe')->info("QB SOAP service [$method] called.  Incoming request: " . print_r($data, TRUE));
    $public_services = ['clientVersion', 'serverVersion', 'authenticate'];

    $request = $this->prepareResponse($method, $data);

    $callback = "call_$method";
    $response = NULL;

    // If the method being requested requires a validated user, do that now.
    if (!in_array($method, $public_services)) {
      // The request must have a ticket to proceed.
      if (empty($request['ticket'])) {
        return $request;
      }

      $valid = $this->session_manager
        ->setUUID($request['ticket'])
        ->validateSession($method);

      // If the client has a valid ticket and request, log in now.
      if ($valid) {
        $user = User::load($this->session_manager->getUID());
        user_login_finalize($user);

        if (!$user->hasPermission('access quickbooks soap service')) {
          \Drupal::logger('commerce_qbe')->warning('User logged in successfully but didn\'t have Quickbooks SOAP Service access permissions.');
          return $request;
        }
      }
      else {
        \Drupal::logger('commerce_qbe')->error('The user had an invalid session token or made an invalid request.  Aborting communication...');
        return $request;
      }
    }

    // If a valid method method is being called, parse the incoming request
    // and call the method with the parsed data passed in.
    if (is_callable([$this, $callback])) {
      // Prepare the response to the client.
      $response = $this->$callback($request);
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
    $response = $data[0];
    $response->$method_name = '';

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
    $result = array('', 'nvu');

    // If the service isn't set for whatever reason we can't continue.
    if (!isset($this->userAuthInterface)) {
      \Drupal::logger('commerce_qbe')->error("User Auth service couldn't be initialized.");
    }
    else {
      $uid = $this->userAuthInterface->authenticate($strUserName, $strPassword);

      if ($uid === FALSE) {
        \Drupal::logger('commerce_qbe')->error("Invalid login credentials, aborting quickbooks SOAP service.");
      }
      else {
        \Drupal::logger('commerce_qbe')->info("Quickbooks user $strUserName successfully connected!  Commencing data exchange with client.");

        $uuid = \Drupal::service('uuid')->generate();
        $this->session_manager->startSession($uuid, $uid);

        $result = array($uuid, '');
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
