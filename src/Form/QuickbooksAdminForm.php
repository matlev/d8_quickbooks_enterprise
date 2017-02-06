<?php

namespace Drupal\commerce_quickbooks_enterprise\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QuickbooksAdminForm.
 *
 * @package Drupal\commerce_quickbooks_enterprise\Form
 */
class QuickbooksAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_quickbooks_enterprise.QuickbooksAdmin',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quickbooks_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_quickbooks_enterprise.QuickbooksAdmin');

    // Check if we have a GUID before creating one.
    if (empty($config->get('qwc_owner_id'))) {
      $uuid = \Drupal::service('uuid');
      $qwc_owner_id = $uuid->generate();
    }
    else {
      $qwc_owner_id = $config->get('qwc_owner_id');
    }

    $form['export_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Export configuration'),
    ];
    $form['export_settings']['exportables'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exportables'),
      '#description' => $this->t('Specify which kinds of exportables you want to enable.'),
      '#options' => array(
        'add_customer' => $this->t('Customers'),
        'add_inventory_product' => $this->t('Inventory products'),
        'add_non_inventory_product' => $this->t('Non-inventory products'),
        'add_invoice' => $this->t('Invoices'),
        'mod_invoice' => $this->t('Modified invoices'),
        'add_sales_receipt' => $this->t('Sales receipts'),
        'add_payment' => $this->t('Payments')
      ),
      '#default_value' => $config->get('exportables'),
    ];
    $form['export_settings']['retry_exports'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Retry failed exports'),
      '#description' => $this->t('Determine whether failed exports should be retried or not.'),
      '#default_value' => $config->get('retry_exports'),
    ];

    $form['accounts'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product Export Settings'),
      '#description' => $this->t('The default account names for Product sales; these are mandatory and must match the full display name in Quickbooks, but can be changed at any time.'),
    ];
    $form['accounts']['main_income_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main income account'),
      '#description' => $this->t('When exporting products through Quickbooks WebConnect, the resulting Quickbooks products will be linked to this account.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('main_income_account'),
      '#required' => TRUE,
    ];
    $form['accounts']['cogs_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('COGS account'),
      '#description' => $this->t('Provide the name of the Cost of Goods Sold (COGS) account for exported products.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('cogs_account'),
      '#required' => TRUE,
    ];
    $form['accounts']['assets_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assets account'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('assets_account'),
      '#required' => TRUE,
    ];

    $form['shipping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Shipping settings'),
    ];
    $form['shipping']['shipping_service'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipping service'),
      '#description' => $this->t('Provide the name of default shipping service-item so that Quickbooks can keep track of shipping charges.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('shipping_service'),
    ];
    $form['shipping']['shipping_service_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipping service description'),
      '#description' => $this->t('Provide a description for the shipping charge. This will show up on the Quickbooks invoice.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('shipping_service_description'),
    ];

    $form['custom_ids'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom ID Settings'),
      '#description' => $this->t('If your Quickbooks setup requires custom invoice and payment IDs, you can set a prefix here to prepend to generated reference numbers.')
    ];
    $form['custom_ids']['quickbooks_invoice_number_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quickbooks invoice number prefix'),
      '#description' => $this->t('Specify the prefix to the order id to be used when generating the invoice number.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('quickbooks_invoice_number_prefix'),
    ];
    $form['custom_ids']['quickbooks_payment_reference_num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quickbooks payment reference number prefix'),
      '#description' => $this->t('Specify the prefix to the order id to be used when generating the payment reference number.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('quickbooks_payment_reference_num'),
    ];

    $form['logging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Message Logging Settings'),
    ];
    $form['logging']['log_file_settings'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log file settings'),
      '#description' => $this->t('Write QB Webconnect update details to log file.'),
      '#options' => array(
        'internal_logging' => $this->t('Enable Drupal message logging'),
        'external_logging' => $this->t('Enable external logging'),
        'truncate_log' => $this->t('Truncate log before update')
      ),
      '#default_value' => $config->get('log_file_settings'),
    ];
    $form['logging']['path_to_log_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to log file'),
      '#description' => $this->t('Must be writeable by webserver.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('path_to_log_file'),
    ];

    $form['qwc_owner_id'] = [
      '#type' => 'hidden',
      '#value' => $qwc_owner_id,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_quickbooks_enterprise.QuickbooksAdmin')
      ->set('exportables', $form_state->getValue('exportables'))
      ->set('retry_exports', $form_state->getValue('retry_exports'))
      ->set('main_income_account', $form_state->getValue('main_income_account'))
      ->set('cogs_account', $form_state->getValue('cogs_account'))
      ->set('assets_account', $form_state->getValue('assets_account'))
      ->set('shipping_service', $form_state->getValue('shipping_service'))
      ->set('shipping_service_description', $form_state->getValue('shipping_service_description'))
      ->set('quickbooks_invoice_number_prefix', $form_state->getValue('quickbooks_invoice_number_prefix'))
      ->set('quickbooks_payment_reference_num', $form_state->getValue('quickbooks_payment_reference_num'))
      ->set('log_file_settings', $form_state->getValue('log_file_settings'))
      ->set('path_to_log_file', $form_state->getValue('path_to_log_file'))
      ->set('qwc_owner_id', $form_state->getValue('qwc_owner_id'))
      ->save();
  }

}
