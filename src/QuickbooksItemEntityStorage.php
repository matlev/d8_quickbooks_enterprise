<?php

namespace Drupal\commerce_quickbooks_enterprise;

use Drupal\commerce_quickbooks_enterprise;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

class QuickbooksItemEntityStorage extends SqlContentEntityStorage implements QuickbooksItemEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadNextPriorityItem(array $priorities) {
    $result = [];

    if (empty($priorities)) {
      \Drupal::logger('commerce_qbe_storage')->info('no priority list given');
      $result = $this->loadNextPendingItem();
    }
    else {
      // Loop through each priority until we get an Item
      foreach ($priorities as $priority) {
        $query = $this->getQuery();
        $result = $query
          ->condition('status', 1)
          ->condition('item_type', $priority)
          ->execute();

        if (!empty($result)) {
          break;
        }
      }
    }

    return is_array($result) ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadNextPendingItem() {
    $query = $this->getQuery();
    $result = $query
      ->condition('status', 1)
      ->execute();

    return reset($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMostRecentExport() {
    $query = $this->getQuery();
    $result = $query
      ->condition('status', 1)
      ->exists('exported')
      ->sort('exported', 'DESC')
      ->execute();

    return reset($result);
  }

  /**
   * Get all pending exports.
   *
   * @return array
   */
  public function loadAllPendingItems() {
    $query = $this->getQuery();
    $result = $query
      ->condition('status', 1)
      ->execute();

    return $result;
  }

  /**
   * Get all completed exports.
   *
   * @return array
   */
  public function loadAllDoneItems() {
    $query = $this->getQuery();
    $result = $query
      ->condition('status', 0)
      ->execute();

    return $result;
  }
}
