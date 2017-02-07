<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_quickbooks_enterprise\Entity\QBItem;
use Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;

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
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $entityQuery;

  /**
   * Storage handler for QB Items.
   *
   * @var \Drupal\commerce_quickbooks_enterprise\QuickbooksItemEntityStorageInterface
   */
  private $qbItemStorage;

  /**
   * Module handler for alter hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueryFactory $entityQuery,
    UserAuthInterface $userAuthInterface,
    QBXMLParser $parser,
    SoapSessionManager $sessionManager,
    ValidatorInterface $validator,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->qbItemStorage = $entity_type_manager->getStorage('commerce_qbe_qbitem');
    $this->entityQuery = $entityQuery;
    $this->userAuthInterface = $userAuthInterface;
    $this->qbxmlParser = $parser;
    $this->sessionManager = $sessionManager;
    $this->validator = $validator;
    $this->moduleHandler = $moduleHandler;
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
        /** @var UserInterface $user */
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


  /****************************************************
   * Private helper functions                         *
   ****************************************************/

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
   * Calculate the completion progress of the current SOAP session.
   *
   * @return int
   */
  private function getCompletionProgress() {
    $done = count($this->qbItemStorage->loadAllDoneItems());
    $todo = count($this->qbItemStorage->loadAllPendingItems());

    return (int) (100 * ($done/($done + $todo)));
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
   *
   * @TODO: Reset failed exports id requested.
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
   *
   * @TODO: Break out QBItem lookup loop and export preparation block into their
   *   own function.
   * @TODO: Don't delete entities on validation failure or export failure,
   *   set to FAIL status instead and move on.
   */
  public function call_sendRequestXML(\stdClass $request) {
    \Drupal::logger('commerce_qbe')->info("Request received, searching for exports in the Queue...");

    $not_ready = TRUE;

    // Go through the queue looking for a valid item.
    do {
      $qb_item_id = $this->qbItemStorage->loadNextPriorityItem($this->itemPriorities);

      /** @var QBItemInterface $qb_item */
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

    // Allow other modules to alter the properties if necessary
    $this->moduleHandler->alter('commerce_quickbooks_enterprise_rxml_properties', $properties, $qb_item);

    // Build the XML response for the request and return it.
    $this->qbxmlParser->buildResponseXML($item_type, $properties);
    $qbxml = $this->qbxmlParser->getResponseXML();

    // If we failed, mark the current Item as a failure and try the next Item.
    if (empty($qbxml)) {
      \Drupal::logger('commerce_qbe_request')->error('Failed to generate xml.');
      $qb_item->setStatus($this->status['CQBWC_ERROR']);
      $qb_item->setExportTime(REQUEST_TIME);
      $qb_item->save();

      return $this->call_sendRequestXML($request);
    }
    else {
      $qb_item->setExportTime(REQUEST_TIME);
      $qb_item->save();

      $request->sendRequestXMLResult = $qbxml;
      return $request;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function call_receiveResponseXML(\stdClass $request) {
    $status = $this->status['CQBWC_DONE'];
    $retry = FALSE;

    $qb_item_id = $this->qbItemStorage->loadMostRecentExport();

    /** @var QBItemInterface $qb_item */
    $qb_item = QBItem::load($qb_item_id);

    if (!empty($qb_item)) {
      // Parse any errors if we have them to decide our next action.
      if (!empty($request->response)) {
        $this->qbxmlParser
          ->setRequestXML($request->response)
          ->parseQuickbooksErrors();
        $errors = $this->qbxmlParser->getErrorList();
      }
      else {
        $errors = [
          "statusCode" => $request->hresult,
          "statusMessage" => $request->message,
        ];
      }

      foreach ($errors as $error) {
        $error_msg = "Response error statusCode: " . print_r($error, TRUE);

        \Drupal::logger('commerce_qbe_errors')->error($error_msg);

        // Ignore statusCode 3100 (already exists).
        if ($error['statusCode'] == "3100") {
          continue;
        }

        // 3180 is a temporary error with no clear reason. Just retry it.
        if ($error['statusCode'] == "3180") {
          $retry = TRUE;
        }

        $status = $this->status['CQBWC_ERROR'];
      }

      if (!$retry) {
        $qb_item->setStatus($status);
        $qb_item->save();

        if ($status == $this->status['CQBWC_DONE']) {
          // Attach the Quickbooks ID(s) to the original entity now
          $response_id = $this->qbxmlParser->getResponseIds();
          $update = "update_" . $qb_item->getItemType();
          $this->$update($qb_item, $response_id);
        }
      }

      $request->receiveResponseXMLResult = $this->getCompletionProgress();
    }
    else {
      $request->receiveResponseXMLResult = 100;
    }

    return $request;
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
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    /** @var ProductVariationInterface $product */
    $product = $qb_item->getExportableEntity();

    $properties->product_id = $product->id();
    $properties->sku = $product->getSku();
    $properties->title = $product->getTitle();
    $properties->price = number_format($product->getPrice()->getNumber(), 2, '.', '');
    $properties->income = $config->get('main_income_account');
    $properties->cogs = $config->get('cogs_account');
    $properties->assets = $config->get('assets_account');
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
    /** @var OrderInterface $order */
    $order = $qb_item->getExportableEntity();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingProfile */
    $billingProfile = $order->getBillingProfile()->get('address')->first();

    $billing = [
      'first_name' => $billingProfile->getGivenName(),
      'last_name' => $billingProfile->getFamilyName(),
      'thoroughfare' => $billingProfile->getAddressLine1(),
      'premise' => $billingProfile->getAddressLine2(),
      'locality' => $billingProfile->getLocality(),
      'administrative_area' => $billingProfile->getAdministrativeArea(),
      'postal_code' => $billingProfile->getPostalCode(),
      'country' => $billingProfile->getCountryCode(),
      'name_line' => '',
    ];

    if ($this->moduleHandler->moduleExists('commerce_shipping')) {
      // @TODO: Parse out shipping information here and add to $properties.
    }

    $properties->billing = $billing;
    $properties->email = $order->getEmail();

    // We leave it up to site maintainers to hook into the module and change
    // the phone property themselves, because there is no default 'phone' field.
    $properties->phone = '';
  }

  /**
   * Parse Order entities into a template-ready object.
   *
   * Variables are added to $properties in the order they appear in the twigs.
   *
   * @param \stdClass $properties
   *   The properties object for product templates.
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   *   The current export.
   */
  private function prepare_order_export(QBItemInterface $qb_item, \stdClass &$properties) {
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    /** @var OrderInterface $order */
    $order = $qb_item->getExportableEntity();
    /** @var UserInterface $customer */
    $customer = $order->getCustomer();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billingProfile */
    $billingProfile = $order->getBillingProfile()->get('address')->first();
    $line_items = $order->getItems();

    $properties->order_id = $config->get('quickbooks_invoice_number_prefix') . $order->id();

    // In case this is a mod_invoice call, attempt to set QB ref IDs.
    if ($order->hasField('commerce_qbe_qbid') && $order->hasField('commerce_qbe_edit_sequence')) {
      if (!empty($order->commerce_qbe_qbid->value)) {
        $properties->quickbooks_order_txnid = $order->commerce_qbe_qbid->value;
      }

      if (!empty($order->commerce_qbe_edit_sequence->value)) {
        $properties->quickbooks_order_edit_sequence = $order->commerce_qbe_edit_sequence->value;
      }
    }

    // Try to set User reference.  Ideally this is always set, but not mandatory.
    if ($customer->hasField('commerce_qbe_qbid') && !empty($customer->commerce_qbe_qbid->value)) {
      $properties->customer_quickbooks_listid = $customer->commerce_qbe_qbid->value;
    }
    else {
      $properties->last_name = $billingProfile->getFamilyName();
      $properties->first_name = $billingProfile->getGivenName();
    }

    $properties->date = date("Y-m-d", $order->getChangedTime());
    $properties->ref_number = '';

    // Add billing address information.
    $billing = [
      'thoroughfare' => $billingProfile->getAddressLine1(),
      'premise' => $billingProfile->getAddressLine2(),
      'sub_premise' => '',
      'locality' => $billingProfile->getLocality(),
      'administrative_area' => $billingProfile->getAdministrativeArea(),
      'postal_code' => $billingProfile->getPostalCode(),
      'country' => $billingProfile->getCountryCode(),
    ];
    $properties->billing_address = $billing;

    // Add shipping address and shipping detail information.
    if ($this->moduleHandler->moduleExists('commerce_shipping')) {
      // @TODO: Parse out shipping information here and add to $properties.
    }

    // If the order has payment details, we need to add them for sales receipts.
    $paymentQuery = $this->entityQuery->get('commerce_payment');
    $paymentId = $paymentQuery
      ->condition('order_id', $order->id())
      ->execute();

    if (!empty($paymentId)) {
      $paymentStorage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
      /** @var PaymentInterface $payment */
      $payment = $paymentStorage->load(reset($paymentId));

      $properties->payment_method = $payment->getType()->getLabel();
    }

    // @TODO: Implement tax type when supported.
    // $properties->tax_type = '';

    // Add products from the order.
    if (!empty($line_items)) {
      $properties->products = [];

      foreach ($line_items as $item) {
        $purchasable = $item->getPurchasedEntity();

        // We can only export product variations entities
        if ($purchasable instanceof ProductVariationInterface) {
          // Try to attach the QB ref ID first, if it exists.
          if ($purchasable->hasField('commerce_qbe_qbid') && !empty($purchasable->commerce_qbe_qbid->value)) {
            $product = ['quickbooks_listid' => $purchasable->commerce_qbe_qbid->value];
          }
          else {
            $product = ['sku' => $purchasable->getSku()];
          }

          // Price is always assumed to be in USD, and should be converted as required.
          $product['price'] = number_format($purchasable->getPrice()->getNumber(), 2, '.', '');
          $product['title'] = $item->getTitle();
          $product['quantity'] = $item->getQuantity();

          $properties->products[] = $product;
        }
      }
    }

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
    /** @var PaymentInterface $payment */
    $payment = $qb_item->getExportableEntity();
    /** @var OrderInterface $order */
    $order = $payment->getOrder();
    /** @var UserInterface $customer */
    $customer = $order->getCustomer();
    /** @var AddressItem $billingProfile */
    $billingProfile = $order->getBillingProfile()->get('address')->first();

    // Check if the user exists in Quickbooks already or not.
    if ($customer->hasField('commerce_qbe_qbid') && !empty($customer->commerce_qbe_qbid->value)) {
      $properties->customer_quickbooks_listid = $customer->commerce_qbe_qbid->value;
    }
    else {
      $properties->last_name = $billingProfile->getFamilyName();
      $properties->first_name = $billingProfile->getGivenName();
    }

    $properties->date = date("Y-m-d", $payment->getCapturedTime());
    $properties->ref_number = '';
    $properties->amount = number_format($payment->getAmount()->getNumber(), 2, '.', '');
    $properties->payment_method = $payment
      ->getPaymentGateway()
      ->getPlugin()
      ->getDisplayLabel();

    // Check if we're applying a payment to an exported invoice.
    if ($order->hasField('commerce_qbe_qbid') && !empty($order->commerce_qbe_qbid->value)) {
      $properties->order_txnid = $order->commerce_qbe_qbid->value;
      $properties->order_payment_amount = number_format($payment->getAmount()->getNumber(), 2, '.', '');
    }
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_inventory_product(QBItemInterface $qb_item, $response = array()) {
    /** @var ProductVariationInterface $product */
    $product = $qb_item->getExportableEntity();
    $product->set('commerce_qbe_qbid', $response['qbid']);
    $product->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_non_inventory_product(QBItemInterface $qb_item, $response = array()) {
    /** @var ProductVariationInterface $product */
    $product = $qb_item->getExportableEntity();
    $product->set('commerce_qbe_qbid', $response['qbid']);
    $product->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_customer(QBItemInterface $qb_item, $response = array()) {
    /** @var OrderInterface $order */
    $order = $qb_item->getExportableEntity();
    $customer = $order->getCustomer();
    $customer->set('commerce_qbe_qbid', $response['qbid']);
    $customer->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_invoice(QBItemInterface $qb_item, $response = array()) {
    /** @var OrderInterface $order */
    $order = $qb_item->getExportableEntity();
    $order->set('commerce_qbe_qbid', $response['qbid']);
    $order->set('commerce_qbe_edit_sequence', $response['edit_sequence']);
    $order->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_mod_invoice(QBItemInterface $qb_item, $response = array()) {
    /** @var OrderInterface $order */
    $order = $qb_item->getExportableEntity();
    $order->set('commerce_qbe_edit_sequence', $response['edit_sequence']);
    $order->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_sales_receipt(QBItemInterface $qb_item, $response = array()) {
    /** @var PaymentInterface $payment */
    $payment = $qb_item->getExportableEntity();
    $order = $payment->getOrder();
    $order->set('commerce_qbe_qbid', $response['qbid']);
    $order->set('commerce_qbe_edit_sequence', $response['edit_sequence']);
    $order->save();
  }

  /**
   * Attach the Quickbooks ID to the entity
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface $qb_item
   * @param array $response
   */
  private function update_add_payment(QBItemInterface $qb_item, $response = array()) {
    // @TODO: Add QBID field to Payments.
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
