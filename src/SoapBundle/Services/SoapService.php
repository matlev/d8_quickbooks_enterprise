<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\commerce_quickbooks_enterprise\Entity\QBItem;
use Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
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
  protected $qbxmlParser;

  /**
   * The session manager.
   *
   * Responsible for managing, validating and invalidating SOAP sessions.
   *
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapSessionManager
   */
  protected $sessionManager;

  /**
   * @var \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\ValidatorInterface
   */
  protected $validator;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   * @param \Drupal\user\UserAuthInterface $userAuthInterface
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\QBXMLParser $parser
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\SoapSessionManager $sessionManager
   * @param \Drupal\commerce_quickbooks_enterprise\SoapBundle\Services\ValidatorInterface $validator
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueryFactory $entityQuery,
    UserAuthInterface $userAuthInterface,
    QBXMLParser $parser,
    SoapSessionManager $sessionManager,
    ValidatorInterface $validator
  ) {
    $this->qbItemStorage = $entity_type_manager->getStorage('commerce_qbe_qbitem');
    $this->entityQuery = $entityQuery;
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser = $parser;
    $this->sessionManager = $sessionManager;
    $this->validator = $validator;
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
      if (empty($request->ticket)) {
        return $request;
      }

      $valid = $this->sessionManager
        ->setUUID($request->ticket)
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


  /****************************************************
   * The WSDL defined SOAP service calls              *
   ****************************************************/

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
    \Drupal::logger('commerce_qbe')->info("Request received, searching for exports in the Queue...");

    $not_ready = TRUE;
    $item_type = '';

    // Go through the queue looking for a valid item.
    do {
      $qb_item_id = $this->qbItemStorage->loadNextPriorityItem($this->itemPriorities);
      $qb_item = QBItem::load($qb_item_id);

      // Validate the item data now to ensure it's exportable.
      if (!empty($qb_item)) {
        $item_type = $qb_item->getItemType();
        $valid = $this->validator->validate($item_type, $qb_item);
        if ($valid) {
          // We're ready to move on and prepare the template.
          $not_ready = FALSE;
        }
        else {
          // Remove any invalid items and try again.
          $qb_item->delete();
        }
      }
      else {
        // If there are no more items, then we don't have any work to do.
        \Drupal::logger('commerce_qbe')->info("No items to export, job done.");
        return $request;
      }
    } while ($not_ready);

    \Drupal::logger('commerce_qbe')->info("Exportable item found of type [$item_type]!  Preparing for export...");

    // Now prepare the object we need to pass to templates to build valid XML.
    $properties = new \stdClass();
    switch ($item_type) {
      case 'add_inventory_product':
      case 'add_non_inventory_prodcut':
        $this->prepare_product_export($qb_item, $properties);
        break;

      case 'add_customer':
        $this->prepare_customer_export($qb_item, $properties);
        break;

      case 'add_invoice':
      case 'mod_invoice':
      case 'add_sales_receipt':
        $this->prepare_order_export($qb_item, $properties);
        break;

      case 'add_payment':
        $this->prepare_payment_export($qb_item, $properties);
        break;

      default:
        \Drupal::logger('commerce_qbe')->error("Unable to prepare data for export.  No method found for [$item_type]");
        return $request;
    }

    // Finally, build the XML response for the request and return it.
    $this->qbxmlParser->buildResponseXML($item_type, $properties);
    $request->sendRequestXMLResult = $this->qbxmlParser->getResponseXML();

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


  /****************************************************
   * Export entity processing helpers                 *
   ****************************************************/

  /**
   * Parse Product entities into a template-ready object.
   *
   * @param \stdClass $properties
   *   The properties object for product templates.
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   *   The current export.
   */
  private function prepare_product_export(QBItemInterface $qb_item, \stdClass &$properties) {
    $product = $qb_item->getExportableEntity();

    $properties->product_id = $product->id();
    $properties->sku = $product->getSku();
    $properties->title = $product->getTitle();
    $properties->price = $product->getPrice();
    $properties->income = '';
    $properties->cogs = '';
    $properties->assets = '';
  }

  /**
   * Parse User entities into a template-ready object.
   *
   * @param \stdClass $properties
   *   The properties object for product templates.
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   *   The current export.
   */
  private function prepare_customer_export(QBItemInterface $qb_item, \stdClass &$properties) {
    $customer = $qb_item->getExportableEntity();
    \Drupal::logger('commerce_qbe')->info(print_r($customer, TRUE));
  }

  /**
   * Parse Order entities into a template-ready object.
   *
   * @param \stdClass $properties
   *   The properties object for product templates.
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   *   The current export.
   */
  private function prepare_order_export(QBItemInterface $qb_item, \stdClass &$properties) {

  }

  /**
   * Parse Order entities into a template-ready object.
   *
   * @param \stdClass $properties
   *   The properties object for product templates.
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   *   The current export.
   */
  private function prepare_payment_export(QBItemInterface $qb_item, \stdClass &$properties) {

  }

  /****************************************************
   * Alter hook exposed functions for plugin swapping *
   ****************************************************/

  /**
   * Allow modules to alter the QBXML Parser service.
   *
   * @param Object $service
   * @return $this
   */
  public function setQBXMLParserService($service) {
    $this->qbxmlParser = $service;
    return $this;
  }

  /**
   * Allow modules to alter the SOAP Session Manager service.
   *
   * @param Object $service
   * @return $this
   */
  public function setSoapSessionManagerService($service) {
    $this->sessionManager = $service;
    return $this;
  }

  /**
   * Allows others to change the priority of Items in the queue.
   *
   * $prioirites must be an unkeyed array, where values correspond to the
   * values allowed in the commerce_qbe_qbitem.item_type field.
   *
   * @param array $priorities
   */
  public function setItemPriorities(array $priorities) {
    $this->itemPriorities = $priorities;
  }
}
