<?php

namespace Drupal\commerce_quickbooks_enterprise\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the User event.
 *
 * @see \Drupal\commerce_quickbooks_enterprise\Event\UserEvents
 */
class UserEvent extends Event {

  /**
   * The User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Constructs a new UserEvent
   *
   * @param \Drupal\user\UserInterface $user
   *   The User.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
  }

  /**
   * Gets the User.
   *
   * @return \Drupal\user\UserInterface
   */
  public function getUser() {
    return $this->user;
  }

}
