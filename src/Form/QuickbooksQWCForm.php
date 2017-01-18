<?php
/**
 * Created by PhpStorm.
 * User: mlevasseur
 * Date: 1/17/2017
 * Time: 4:32 PM
 */

namespace Drupal\commerce_quickbooks_enterprise\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class QuickbooksQWCForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quickbooks_qwc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['']
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}