<?php

namespace Drupal\commerce_quickbooks_enterprise\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

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
class commerceQuickbooksEnterpriseItem extends ContentEntityBase implements commerceQuickbooksEnterpriseItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Export status'))
      ->setDescription(t('A boolean indicating whether the Item exported successfully.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}