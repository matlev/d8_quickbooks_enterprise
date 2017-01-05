<?php

namespace Drupal\commerce_quickbooks_enterprise\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Export statuses.
 */
define('QB_EXPORT_PENDING', 0);
define('QB_EXPORT_SUCCESS', 1);
define('QB_EXPORT_FAIL', 2);

/**
 * Defines the commerceQuickbooksEnterpriseItem class.
 *
 * Class commerceQuickbooksEnterpriseItem
 * @package Drupal\commerce_quickbooks_enterprise\Entity
 *
 * @ContentEntityType (
 *   id = 'commerceQuickbooksEnterpriseItem',
 * )
 */
class commerceQuickbooksEnterpriseQBItem extends ContentEntityBase implements commerceQuickbooksEnterpriseQBItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getItemType() {
    return $this->get('item_type')->value;
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
  function getExportableEntity($entity_type_id) {
    return $this->exportable_entity->referencedEntities();
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
    $dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, date($time));

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

    $fields['item_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item Type'))
      ->setDescription(t('The QB Item type expected by Quickbooks.'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Export status'))
      ->setDescription(t('A code indicating whether the Item exported successfully.'))
      ->setDefaultValue(TRUE);

    $fields['exportable_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Exportable Entity'))
      ->setDescription(t('The entity that will be processed and exported to quickbooks.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['exported'] = BaseFieldDefinition::create('date')
      ->setLabel(t('Exported timestamp'))
      ->setDescription(t('The time that the Item was exported.'));

    return $fields;
  }
}