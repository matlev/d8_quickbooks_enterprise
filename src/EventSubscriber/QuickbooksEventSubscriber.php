<?php
/**
 * @file
 * Contains \Drupal\commerce_quickbooks_enterprise\EventSubscriber\QuickbooksEventSubscriber.
 */

namespace Drupal\commerce_quickbooks_enterprise\EventSubscriber;

use Drupal\commerce_quickbooks_enterprise\Entity\QBItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductEvent;
use Drupal\commerce_product\Event\ProductVariationEvent;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvents;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvent;
use Drupal\Core\Entity\ContentEntityInterface;

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
      ProductEvents::PRODUCT_CREATE => 'onProductAlter',
      ProductEvents::PRODUCT_UPDATE => 'onProductAlter',
      ProductEvents::PRODUCT_VARIATION_CREATE => 'onVariationAlter',
      ProductEvents::PRODUCT_VARIATION_UPDATE => 'onVariationAlter',
      UserEvents::USER_CREATE => 'onUserEvent',
      UserEvents::USER_INSERT => 'onUserEvent',
      UserEvents::USER_UPDATE => 'onUserEvent',
    ];

    return $events;
  }

  /**
   * Passes an order entity to the Queue if it hits a specified state.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   */
  public function onOrderTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Depending on where the order came from and if it's been paid or not,
    // we may be exporting an invoice, sales receipt, or a payment.
    // @TODO: Fill out order checking logic.

    // We need to cycle through the order and add any applicable products or
    // customers to the queue if they don't have a Quickbooks Reference ID.
    // @TODO: Parse order components.
  }

  /**
   * Pass a product entity to the Queue
   *
   * Products are only sent to Quickbooks if a new product has been added, or
   * a product has been updated.  The user can toggle this behaviour.
   *
   * @param \Drupal\commerce_product\Event\ProductEvent $event
   */
  public function onProductAlter(ProductEvent $event) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $event->getProduct();

    //\Drupal::logger('commerce_qbe_events')->info(print_r($product, TRUE));
  }

  /**
   * Pass a product variation entity to the Queue
   *
   * @param \Drupal\commerce_product\Event\ProductVariationEvent $event
   */
  public function onVariationAlter(ProductVariationEvent $event) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $event->getProductVariation();

    if (empty($variation->getSku())) {
      return;
    }

    \Drupal::logger('commerce_qbe_events')->info('Adding Product Variation to export queue...');
    if ($variation->hasField('commerce_qbe_qbid')) {
      if (empty($variation->commerce_qbe_qbid->value)) {
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
   * Pass a User entity to the Queue
   *
   * A report sent to Quickbooks must have a customer reference.  Drupal Users
   * are matched to a Quickbooks customer, or a Quickbooks customer is created
   * from a Drupal User.  A Quickbooks customer ref. ID is stored with the User.
   *
   * @param \Drupal\commerce_quickbooks_enterprise\Event\UserEvent $event
   */
  public function onUserEvent(UserEvent $event) {
    /** @var \Drupal\Core\Entity\EntityInterface $user */
    $user = $event->getUser();

    if (empty($user->commerce_qbe_qbid)) {
      $qb_item = QBItem::create();
      $qb_item->setItemType("add_customer");
      $qb_item->setStatus(CQBWC_PENDING);
      $qb_item->setExportableEntity($user);
      $qb_item->setCreatedTime(REQUEST_TIME);
      $qb_item->save();
    }
  }

}
