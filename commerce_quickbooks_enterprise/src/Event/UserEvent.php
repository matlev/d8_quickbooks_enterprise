<?php

namespace Drupal\commerce_quickbooks_enterprise\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the User event.
 *
 * @see \Drupal\commerce_quickbooks_enterprise\Event\UserEvents
 */
class UserEvent extends Event {

  /**
   * The User.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $user;

  /**
   * Constructs a new UserEvent
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The User.
   */
  public function __construct(EntityInterface $user) {
    $this->user = $user;
  }

  /**
   * Gets the User.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getUser() {
    return $this->user;
  }

}
