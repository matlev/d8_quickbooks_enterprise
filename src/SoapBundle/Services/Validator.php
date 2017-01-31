<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface;

/**
 * Class Validator
 *
 * Validates entities that are queued for export to Quickbooks.
 *
 * A swappable validator plugin, because what is invalid for one user may be
 * valid for another, and vice-versa.  This plugin invalidates entities that
 *   a) Already have a quickbooks reference ID (no need to export)
 *   b) Are empty
 *   c) Have malformed data
 *
 * @package Drupal\commerce_quickbooks_enterprise\SoapBundle\Services
 */
class Validator extends ValidatorBase {

  public function validate_add_inventory_product(QBItemInterface $data) {
    \Drupal::logger('commerce_qbe')->info('Validating add_inventory_product item');
    return TRUE;
  }

  public function validate_add_non_inventory_product(QBItemInterface $data) {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate_add_invoice(QBItemInterface $data) {
    // TODO: Implement validate_add_invoice() method.

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate_add_sales_receipt(QBItemInterface $data) {
    // TODO: Implement validate_add_sales_receipt() method.

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate_mod_invoice(QBItemInterface $data) {
    // TODO: Implement validate_mod_invoice() method.

    return TRUE;
  }
}