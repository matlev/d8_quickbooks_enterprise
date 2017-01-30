<?php

namespace Drupal\commerce_quickbooks_enterprise;

use Drupal\Core\Entity\ContentEntityStorageInterface;

interface QuickbooksItemEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Load the next exportable Item according to a given priority.
   *
   * @param array $priorities
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\QBItem|null
   */
  public function loadNextPriorityItem(array $priorities);

  /**
   * Get the next Item waiting to be exported.
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\QBItem|null
   */
  public function loadNextPendingItem();

}
