<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
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
  private $sessionManager;

  /**
   * Entity Query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  private $entityQuery;

  /**
   * Storage handler for QB Items.
   *
   * @var \Drupal\commerce_quickbooks_enterprise\QuickbooksItemEntityStorageInterface
   */
  private $qbItemStorage;

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
   * The default order in which to process exportable QB Items.
   *
   * @var array
   */
  protected $itemPriorities = [
    'add_customer',
    'add_inventory_product',
    'add_non_inventory_product',
    'add_invoice',
    'mod_invoice',
    'add_sales_receipt',
    'add_payment',
  ];

  /**
   * The list of possible statuses for an export Item.
   *
   * @var array
   */
  private $status = [
    'CQBWC_PENDING' => 1,
    'CQBWC_DONE' => 0,
    'CQBWC_ERROR' => -1,
  ];

  /**
   * SoapService constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $entityQuery
   * @param \Drupal\user\UserAuthInterface $userAuthInterface
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\QBXMLParser $parser
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapSessionManager $sessionManager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueryInterface $entityQuery,
    UserAuthInterface $userAuthInterface,
    QBXMLParser $parser,
    SoapSessionManager $sessionManager
  ) {
    $this->qbItemStorage = $entity_type_manager->getStorage('commerce_qbe_qbitem');
    $this->entityQuery = $entityQuery;
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser = $parser;
    $this->sessionManager = $sessionManager;
  }

  /**
   * {@inheritDoc}
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

      $valid = $this->sessionManager
        ->setUUID($request['ticket'])
        ->validateSession($method);

      // If the client has a valid ticket and request, log in now.
      if ($valid) {
        $user = User::load($this->sessionManager->getUID());
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
   *   The Quickbooks method being called.
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
   * Allows others to change the priority of Items in the queue.
   *
   * $prioirites must be an unkeyed array, where values correspond to the
   * values allowed in the commerce_qbe_qbitem.item_type field.
   *
   * @param array $priorities
   */
  public function changeItemPriorities(array $priorities) {
    $this->itemPriorities = $priorities;
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
        $this->sessionManager->startSession($uuid, $uid);

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
    $qb_item = $this->qbItemStorage->loadNextPriorityItem($this->itemPriorities);

    if (!empty($qb_item)) {
      
    }
    else {
      \Drupal::logger('commerce_qbe')->info('Nothing to export, jobs finished.');
    }

    return $request;
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
    $query = $this->entityQuery->get('commerce_qbe_qbitem');
    $query->condition('status', $this->status['CQBWC_PENDING'], '=');
    $query->count();

    $pending_items = $query->execute();

    if ($pending_items == 0) {
      $request->getLastErrorResult = 'No jobs remaining';
    }

    return $request;
  }

  /**
   * {@inheritDoc}
   */
  public function call_closeConnection(\stdClass $request) {
    $this->sessionManager->closeSession();
    $request->closeConnectionResult = 'OK';
    return $request;
  }
}
