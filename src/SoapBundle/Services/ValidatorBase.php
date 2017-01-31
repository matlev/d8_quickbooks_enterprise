<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface;

/**
 * The base validator class for Quickbooks.
 *
 * A module overriding the validator service should extend this class.
 *
 * The three complex exportable types (add/mod invoice, add sales receipt)
 * should always have a validation function to ensure that no unwanted data is
 * being pushed to Quickbooks.
 *
 * @package Drupal\commerce_quickbooks_enterprise\SoapBundle\Services
 */
abstract class ValidatorBase implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate($type, QBItemInterface $data) {
    $callable = "validate_$type";

    return is_callable([$this, $callable])
      ? $this->$callable($data)
      : TRUE;
  }

  /**
   * Validates data being sent to an invoice.
   *
   * @param mixed $data
   *   The data being exported.
   * @return bool
   *   TRUE if $data is valid, FALSE otherwise.
   */
  public abstract function validate_add_invoice(QBItemInterface $data);

  /**
   * Validates data being used to modify an invoice.
   *
   * @param mixed $data
   *   The data being exported.
   * @return bool
   *   TRUE if $data is valid, FALSE otherwise.
   */
  public abstract function validate_mod_invoice(QBItemInterface $data);

  /**
   * Validates data being sent to a sales receipt.
   *
   * @param mixed $data
   *   The data being exported.
   * @return bool
   *   TRUE if $data is valid, FALSE otherwise.
   */
  public abstract function validate_add_sales_receipt(QBItemInterface $data);
}