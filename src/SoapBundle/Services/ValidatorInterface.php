<?php

namespace Drupal\commerce_quickbooks_enterprise\SoapBundle\Services;

use Drupal\commerce_quickbooks_enterprise\Entity\QBItemInterface;

interface ValidatorInterface {

  /**
   * Validates data being exported to Quickbooks.
   *
   * @param string $type
   *   The Quickbooks exportable type.
   * @param mixed $data
   *   The data being exported.
   *
   * @return bool
   *   TRUE if $data is valid or $type does not require validation,
   *   FALSE if $data is invalid.
   */
  public function validate($type, QBItemInterface $data);

}
