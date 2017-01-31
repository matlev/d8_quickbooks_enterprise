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
        $query->condition('status', 1);
        $query->condition('item_type', $priority);
        $result = $query->execute();

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
    $query->condition('status', 1);
    $result = $query->execute();

    if (empty($result)) {
      return [];
    }
    else {
      $items = $this->loadMultiple($result);
      return reset($items);
    }
  }
}
