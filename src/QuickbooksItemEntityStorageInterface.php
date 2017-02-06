<?php

namespace Drupal\commerce_quickbooks_enterprise;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

interface QuickbooksItemEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Check if the given entity is already queued for export.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for existence.
   * @param null $export_type
   *   An optional string to clarify what export type may store the entity.
   *
   * @return mixed
   */
  public function exportExists(EntityInterface $entity, $export_type = NULL);

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
