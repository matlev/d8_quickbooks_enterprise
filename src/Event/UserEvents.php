<?php

namespace Drupal\commerce_quickbooks_enterprise\Event;

final class UserEvents {

  /**
   * Name of the event fired after creating a user.
   *
   * @Event
   *
   * @see \Drupal\commerce_quickbooks_webconnect\Event\UserEvent
   */
  const USER_CREATE = 'commerce_quickbooks_enterprise.user.create';

  /**
   * Name of the event fired after inserting a user into the database.
   *
   * @Event
   *
   * @see \Drupal\commerce_quickbooks_webconnect\Event\UserEvent
   */
  const USER_INSERT = 'commerce_quickbooks_enterprise.user.insert';

  /**
   * Name of the event fired after updating a user.
   *
   * @Event
   *
   * @see \Drupal\commerce_quickbooks_webconnect\Event\UserEvent
   */
  const USER_UPDATE = 'commerce_quickbooks_enterprise.user.update';
}
