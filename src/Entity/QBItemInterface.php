<?php

namespace Drupal\commerce_quickbooks_enterprise\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

interface QBItemInterface extends ContentEntityInterface {

  /**
   * Gets the Item type.
   *
   * @return string
   *   The exportable Item type.
   */
  public function getItemType();

  /**
   * Retrieves the export status of the QB Item.
   *
   * @return int
   *   The numerical status code.
   */
  public function getStatus();

  /**
   * Sets the export status of the QB Item.
   *
   * @param int $status
   *   The status code [
   *     0 => not exported,
   *     1 => successfully exported,
   *     2 => failed
   *   ]
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\commerceQuickbooksEnterpriseQBItemInterface
   *   The called QBItem entity.
   */
  public function setStatus($status);

  /**
   * Retrieve the referenced entity to be exported.
   *
   * @return EntityInterface
   *   The entity associated with the QB Item
   */
  public function getExportableEntity();

  /**
   * Return information about the exportable entity.
   *
   * @return array
   *   An array with keys entity_type, entity_id
   */
  public function getExportableEntityId();

  /**
   * Set the entity to be exported to Quickbooks.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\commerceQuickbooksEnterpriseQBItemInterface
   *   The called QBItem entity.
   */
  public function setExportableEntity(EntityInterface $entity);

  /**
   * Gets the QB Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the QBItem entity.
   */
  public function getCreatedTime();

  /**
   * Sets the QB Item creation timestamp.
   *
   * @param int $timestamp
   *   The QB Item creation timestamp.
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\commerceQuickbooksEnterpriseQBItemInterface
   *   The called QBItem entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Retrieve the export time.
   *
   * @return string
   *   The text representation of the date.
   */
  public function getExportTime();

  /**
   * Set the Export time of the QB Item.
   *
   * @param $timestamp
   *   An ISO 8601 string.
   *
   * @return \Drupal\commerce_quickbooks_enterprise\Entity\commerceQuickbooksEnterpriseQBItemInterface
   *   The called QBItem entity.
   */
  public function setExportTime($timestamp);
}
