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

  /**
   * Retrieve the most recently exported QB Item
   *
   * Usually used for the purpose of selecting a QB Item entity in the
   * receiveResponseXML that was just sent off to Quickbooks in order to attach
   * the returned reference ID to the Drupal entity stored in the QB Item.
   *
   * @return int
   *   The ID of the last pending exported Item.
   */
  public function loadMostRecentExport();

}
