<?php
/**
 * @file
 * Contains \Drupal\commerce_quickbooks_enterprise\EventSubscriber\QuickbooksEventSubscriber.
 */

namespace Drupal\commerce_quickbooks_enterprise\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductEvent;
use Drupal\commerce_product\Event\ProductVariationEvent;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvents;
use Drupal\commerce_quickbooks_enterprise\Event\UserEvent;
use Drupal\Core\Entity\ContentEntityInterface;

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
    //
    // @TODO: support PURCHASABLE ENTITIES
    // @TODO: PRODUCTS should be checked for a SKU
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

    // @TODO: Check config before continuing.

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

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
    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    // @TODO: Check config before continuing.

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $event->getProduct();


  }

  /**
   * Pass a product variation entity to the Queue
   *
   * @param \Drupal\commerce_product\Event\ProductVariationEvent $event
   */
  public function onVariationAlter(ProductVariationEvent $event) {
    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    // @TODO: Check config before continuing.

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $event->getProductVariation();


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
    /** @var \Drupal\Core\Config\ImmutableConfig Qb Enterprise $config */
    $config = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    // @TODO: Check config before continuing.

    /** @var \Drupal\Core\Entity\EntityInterface $user */
    $user = $event->getUser();


  }

  /**
   * Breaks down an entity into Quickbooks components, saving them in the Queue.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  private function saveDataToQueue(ContentEntityInterface $entity) {

  }
}
