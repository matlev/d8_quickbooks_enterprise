<?php
/**
 * @file
 * Contains \Drupal\commerce_quickbooks_enterprise\EventSubscriber\QuickbooksEventSubscriber.
 */

namespace Drupal\commerce_quickbooks_enterprise\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_quickbooks_enterprise\Entity\QBItem;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationEvent;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvents;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvent;

define('CQBWC_PENDING', 1);

/**
 * Event Subscriber QuickbooksEventSubscriber
 *
 * Class QuickbooksEventSubscriber
 * @package Drupal\commerce_quickbooks_enterprise\EventSubscriber
 */
class QuickbooksEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => 'onOrderTransition',
      'commerce_order.validate.post_transition' => 'onOrderTransition',
      'commerce_order.fulfill.post_transition' => 'onOrderTransition',
      'commerce_payment.capture.post_transition' => 'onPaymentTransition',
      'commerce_payment.authorize_capture.post_transition' => 'onPaymentTransition',
      ProductEvents::PRODUCT_VARIATION_CREATE => 'onVariationAlter',
      ProductEvents::PRODUCT_VARIATION_UPDATE => 'onVariationAlter',
    ];

    return $events;
  }

  /**
   * Passes an order entity to the Queue if it hits a specified state.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   */
  public function onOrderTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');
    $exportable = $config->get('exportables');

    // If the order has a Quickbooks ID and Edit Sequence, it's a mod_invoice.
    if (
      $order->hasField('commerce_qbe_qbid') &&
      $order->hasField('commerce_qbe_edit_sequence') &&
      !empty($order->commerce_qbe_qbid->value) &&
      !empty($order->commerce_qbe_edit_sequence->value)
    ) {
      $export_type = "mod_invoice";
    }
    else {
      $export_type = "add_invoice";
    }

    // Do nothing if this type of export is disabled.
    if (empty($exportable[$export_type])) {
      return;
    }

    // Orders require a bit of work because we have to determine what stage it's
    // coming from and where it's going before we can begin to export.
    $from = $event->getFromState()->getId();
    $to = $event->getToState()->getId();

    // Do nothing if the order is canceled.
    if ($to == 'canceled') {
      return;
    }

    // We don't want to export an incomplete invoice.
    if ($to != 'completed' && $export_type == 'add_invoice') {
      return;
    }

    // We've passed all of our failing cases, now we can export the order and its components.
    // Export the customer, if needed.
    $this->createCustomerExport($order);

    // Export products, if needed.
    $items = $order->getItems();
    foreach ($items as $item) {
      $purchasable = $item->getPurchasedEntity();

      // We can only export product variations entities
      if ($purchasable instanceof ProductVariationInterface) {
        $this->createProductExport($purchasable);
      }
    }

    // Finally, export the order itself.
    /** @var \Drupal\commerce_quickbooks_enterprise\Entity\QBItem $qb_item */
    $qb_item = QBItem::create();
    $qb_item->setItemType($export_type);
    $qb_item->setStatus(CQBWC_PENDING);
    $qb_item->setExportableEntity($order);
    $qb_item->setCreatedTime(REQUEST_TIME);
    $qb_item->save();

    \Drupal::logger('commerce_qbe_events')->info('Added Invoice Order to export queue!');
  }

  /**
   * Process and adds a Payment to the export queue.
   *
   * Optionally attempts to add a Sales Receipt export.
   * @TODO: Don't export payments if a sales receipt is exported.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   */
  public function onPaymentTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');
    $exportable = $config->get('exportables');

    /** @var PaymentInterface $payment */
    $payment = $event->getEntity();
    /** @var OrderInterface $order */
    $order = $payment->getOrder();

    if (!empty($exportable['add_sales_receipt'])) {
      /** @var \Drupal\commerce_quickbooks_enterprise\Entity\QBItem $qb_item */
      $qb_item = QBItem::create();
      $qb_item->setItemType('add_sales_receipt');
      $qb_item->setStatus(CQBWC_PENDING);
      $qb_item->setExportableEntity($order);
      $qb_item->setCreatedTime(REQUEST_TIME);
      $qb_item->save();

      \Drupal::logger('commerce_qbe_events')->info('Added Sales Receipt to export queue!');
    }

    if (!empty($exportable['add_payment'])) {
      $qb_item = QBItem::create();
      $qb_item->setItemType('add_payment');
      $qb_item->setStatus(CQBWC_PENDING);
      $qb_item->setExportableEntity($payment);
      $qb_item->setCreatedTime(REQUEST_TIME);
      $qb_item->save();

      \Drupal::logger('commerce_qbe_events')->info('Added Payment to export queue!');
    }
  }

  /**
   * Pass a product variation entity to the Queue
   *
   * @param \Drupal\commerce_product\Event\ProductVariationEvent $event
   */
  public function onVariationAlter(ProductVariationEvent $event) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $event->getProductVariation();
    $this->createProductExport($variation);
  }

  /**
   * Helper function to create a product QB Item export.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   */
  private function createProductExport(ProductVariationInterface $variation) {
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');
    $exportable = $config->get('exportables');

    // Don't do anything if the user disabled this export type.
    if (empty($exportable['add_inventory_product'])) {
      return;
    }

    if (empty($variation->getSku())) {
      return;
    }

    // Only add products that don't currently have a Quickbooks reference ID,
    // and are not currently queued up for export.
    if ($variation->hasField('commerce_qbe_qbid')) {
      $continue = TRUE;

      if (empty($variation->commerce_qbe_qbid->value)) {
        $continue &= TRUE;
      }

      if ($continue) {
        /** @var \Drupal\commerce_quickbooks_enterprise\QuickbooksItemEntityStorageInterface $qbItemStorage */
        $qbItemStorage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('commerce_qbe_qbitem');
        $continue &= !$qbItemStorage->exportExists($variation);;
      }

      if ($continue) {
        /** @var \Drupal\commerce_quickbooks_enterprise\Entity\QBItem $qb_item */
        $qb_item = QBItem::create();
        $qb_item->setItemType("add_inventory_product");
        $qb_item->setStatus(CQBWC_PENDING);
        $qb_item->setExportableEntity($variation);
        $qb_item->setCreatedTime(REQUEST_TIME);
        $qb_item->save();

        \Drupal::logger('commerce_qbe_events')->info('Added Product Variation to export queue!');
      }
    }
  }

  /**
   * Helper function to create a user QB Item export.
   *
   * We pass in the whole order and store it because we need to access the
   * billing and shipping information from the user, as well as First and Last
   * name in order to create a proper Customer for Quickbooks.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  private function createCustomerExport(OrderInterface $order) {
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');
    $exportable = $config->get('exportables');

    // Don't do anything if the user disabled this export type.
    if (empty($exportable['add_customer'])) {
      return;
    }

    // Pull out the customer to test if it's been exported before.
    $user = $order->getCustomer();

    // Only add customers that don't currently have a Quickbooks reference ID
    if ($user->hasField('commerce_qbe_qbid')) {
      $continue = TRUE;

      if (empty($user->commerce_qbe_qbid->value)) {
        $continue &= TRUE;
      }

      if ($continue) {
        /** @var \Drupal\commerce_quickbooks_enterprise\QuickbooksItemEntityStorageInterface $qbItemStorage */
        $qbItemStorage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('commerce_qbe_qbitem');
        $continue &= !$qbItemStorage->exportExists($order, 'add_customer');
      }

      if ($continue) {
        /** @var \Drupal\commerce_quickbooks_enterprise\Entity\QBItem $qb_item */
        $qb_item = QBItem::create();
        $qb_item->setItemType("add_customer");
        $qb_item->setStatus(CQBWC_PENDING);
        $qb_item->setExportableEntity($order);
        $qb_item->setCreatedTime(REQUEST_TIME);
        $qb_item->save();

        \Drupal::logger('commerce_qbe_events')->info('Added Product Variation to export queue!');
      }
    }
  }
}
