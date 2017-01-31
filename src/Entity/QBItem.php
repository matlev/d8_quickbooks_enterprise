<?php

namespace Drupal\commerce_quickbooks_enterprise\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Export statuses.
 */
define('QB_EXPORT_PENDING', 0);
define('QB_EXPORT_SUCCESS', 1);
define('QB_EXPORT_FAIL', -1);

/**
 * Defines the commerceQuickbooksEnterpriseItem class.
 *
 * Class commerceQuickbooksEnterpriseItem
 * @package Drupal\commerce_quickbooks_enterprise\Entity
 *
 * @ContentEntityType (
 *   id = "commerce_qbe_qbitem",
 *   label = @Translation("Quickbooks Item"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_quickbooks_enterprise\QuickbooksItemEntityStorage",
 *   },
 *   admin_permission = "access content",
 *   base_table = "commerce_quickbooks_enterprise_qbitem",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *   },
 * )
 */
class QBItem extends ContentEntityBase implements QBItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getItemType() {
    return $this->get('item_type')->value;
  }

  public function setItemType($type) {
    $this->set('item_type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $valid_status = array(QB_EXPORT_PENDING, QB_EXPORT_SUCCESS, QB_EXPORT_FAIL);

    if (in_array($status, $valid_status)) {
      $this->set('status', $status);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportableEntity() {
    return $this->get('exportable_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportableEntityId() {
    $entity = $this->get('exportable_entity');

    return array(
      'entity_type' => $entity->target_type,
      'entity_id' => $entity->target_id,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setExportableEntity(EntityInterface $entity) {
    $this->set('exportable_entity', array('entity' => $entity));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportTime($format = \DateTime::ISO8601) {
    $time = $this->get('exported')->value;
    $dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $time);

    return $dateTime->format($format);
  }

  /**
   * {@inheritdoc}
   */
  public function setExportTime($iso_string) {
    $this->set('created', $iso_string);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['item_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Item Type'))
      ->setDescription(t('The QB Item type expected by Quickbooks.'))
      ->setSettings(array(
        'allowed_values' => array(
          'add_customer' => 'Add Customer',
          'add_inventory_product' => 'Add Inventory Product',
          'add_non_inventory_product' => 'Add Non-Inventory Product',
          'add_invoice' => 'Add Invoice',
          'mod_invoice' => 'Mod Invoice',
          'add_sales_receipt' => 'Add Sales Receipt',
          'add_payment' => 'Add Payment',
        ),
      ))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Export status'))
      ->setDescription(t('A code indicating whether the Item exported successfully.'))
      ->setDefaultValue(TRUE);

    $fields['exportable_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Exportable Entity'))
      ->setDescription(t('The entity that will be processed and exported to quickbooks.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['exported'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Exported timestamp'))
      ->setDescription(t('The time that the Item was exported.'));

    return $fields;
  }
}
