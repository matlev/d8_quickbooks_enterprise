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
    $form['main_income_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main income account'),
      '#description' => $this->t('When exporting products through Quickbooks WebConnect, the resulting Quickbooks products will be linked to this account.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('main_income_account'),
    ];
    $form['cogs_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('COGS account'),
      '#description' => $this->t('Provide the name of the Cost of Goods Sold (COGS) account for exported products.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('cogs_account'),
    ];
    $form['assets_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assets account'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('assets_account'),
    ];
    $form['type_of_item_to_create_in_quickb'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of item to create in quickbooks'),
      '#description' => $this->t('At this point only inventory and on-inventory items are supported.'),
      '#options' => array('Inventory' => $this->t('Inventory'), 'Non-inventory' => $this->t('Non-inventory')),
      '#size' => 2,
      '#default_value' => $config->get('type_of_item_to_create_in_quickb'),
    ];
    $form['shipping_service'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipping service'),
      '#description' => $this->t('Provide the name of default shipping service-item so that Quickbooks can keep track of shipping charges.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('shipping_service'),
    ];
    $form['shipping_service_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shipping service description'),
      '#description' => $this->t('Provide a description for the shipping charge. This will show up on the Quickbooks invoice.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('shipping_service_description'),
    ];
    $form['log_file_settings'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log file settings'),
      '#description' => $this->t('Write QB Webconnect update details to log file.'),
      '#options' => array('Enable logging' => $this->t('Enable logging'), 'Truncate log before update' => $this->t('Truncate log before update')),
      '#default_value' => $config->get('log_file_settings'),
    ];
    $form['path_to_log_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to log file'),
      '#description' => $this->t('Must be writeable by webserver.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('path_to_log_file'),
    ];
    $form['qb_pending_export'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Pending'),
      '#options' => array('Payment failed (hard decline)' => $this->t('Payment failed (hard decline)'), 'Payment failed (soft decline)' => $this->t('Payment failed (soft decline)'), 'Reviewing' => $this->t('Reviewing'), 'Pending' => $this->t('Pending'), 'Processing' => $this->t('Processing')),
      '#default_value' => $config->get('qb_pending_export'),
    ];
    $form['completed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Completed'),
      '#default_value' => $config->get('completed'),
    ];
    $form['canceled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Canceled'),
      '#default_value' => $config->get('canceled'),
    ];
    $form['checkout'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Checkout'),
      '#options' => array('Checkout: Checkout' => $this->t('Checkout: Checkout'), 'Checkout: Shipping' => $this->t('Checkout: Shipping'), 'Checkout: Review' => $this->t('Checkout: Review'), 'Checkout: Payment' => $this->t('Checkout: Payment'), 'Checkout: Complete' => $this->t('Checkout: Complete')),
      '#default_value' => $config->get('checkout'),
    ];
    $form['cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cart'),
      '#default_value' => $config->get('cart'),
    ];
    $form['payment_status_export_trigger'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Payment status export trigger'),
      '#description' => $this->t('When a payment is created having one of these defined statuses, it will create a record in the export queue.'),
      '#options' => array('Card Expired' => $this->t('Card Expired'), 'Method Failure' => $this->t('Method Failure'), 'Method Failure Insufficient Funds' => $this->t('Method Failure Insufficient Funds'), 'Method Failure Limit Exceeded' => $this->t('Method Failure Limit Exceeded'), 'Method Failure Call Issuer' => $this->t('Method Failure Call Issuer'), 'Method Failure Temporary Hold' => $this->t('Method Failure Temporary Hold'), 'Method Failure Generic Decline' => $this->t('Method Failure Generic Decline'), 'Method Failure Hard Decline' => $this->t('Method Failure Hard Decline'), 'Method Failure Gateway Error' => $this->t('Method Failure Gateway Error'), 'Method Failure Gateway Unavailable' => $this->t('Method Failure Gateway Unavailable'), 'Method Failure Gateway Configuration Error' => $this->t('Method Failure Gateway Configuration Error'), 'Pending' => $this->t('Pending'), 'Success' => $this->t('Success'), 'Failure' => $this->t('Failure')),
      '#default_value' => $config->get('payment_status_export_trigger'),
    ];
    $form['allow_payment_hook_exports'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow payment hook exports'),
      '#description' => $this->t('If checked payments will be queued when payment status is achived, regardless of it&#039;s order&#039;s status.'),
      '#default_value' => $config->get('allow_payment_hook_exports'),
    ];
    $form['allow_payment_export_without_inv'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow payment export without invoice reference'),
      '#description' => $this->t('If checked QuickBooks will try to automatically link up payments and invoices for payments exported before their order.'),
      '#default_value' => $config->get('allow_payment_export_without_inv'),
    ];
    $form['exportables'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exportables'),
      '#description' => $this->t('Specify which kinds of exportables you want to enable.'),
      '#options' => array('Add customer' => $this->t('Add customer'), 'Add inventory product' => $this->t('Add inventory product'), 'Add non-inventory product' => $this->t('Add non-inventory product'), 'Add invoice' => $this->t('Add invoice'), 'Modify invoice' => $this->t('Modify invoice'), 'Add sales receipt' => $this->t('Add sales receipt'), 'Add payment' => $this->t('Add payment'), 'Method Failure Hard Decline' => $this->t('Method Failure Hard Decline'), 'Method Failure Gateway Error' => $this->t('Method Failure Gateway Error'), 'Method Failure Gateway Unavailable' => $this->t('Method Failure Gateway Unavailable'), 'Method Failure Gateway Configuration Error' => $this->t('Method Failure Gateway Configuration Error'), 'Pending' => $this->t('Pending'), 'Success' => $this->t('Success'), 'Failure' => $this->t('Failure')),
      '#default_value' => $config->get('exportables'),
    ];
    $form['order_export_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Order export type'),
      '#description' => $this->t('Export action to take when orders are created.'),
      '#options' => array('Add invoice' => $this->t('Add invoice'), 'Modify invoice' => $this->t('Modify invoice'), 'Add sales receipt' => $this->t('Add sales receipt'), 'Add invoice' => $this->t('Add invoice'), 'Modify invoice' => $this->t('Modify invoice'), 'Add sales receipt' => $this->t('Add sales receipt'), 'Add payment' => $this->t('Add payment'), 'Method Failure Hard Decline' => $this->t('Method Failure Hard Decline'), 'Method Failure Gateway Error' => $this->t('Method Failure Gateway Error'), 'Method Failure Gateway Unavailable' => $this->t('Method Failure Gateway Unavailable'), 'Method Failure Gateway Configuration Error' => $this->t('Method Failure Gateway Configuration Error'), 'Pending' => $this->t('Pending'), 'Success' => $this->t('Success'), 'Failure' => $this->t('Failure')),
      '#size' => 3,
      '#default_value' => $config->get('order_export_type'),
    ];
    $form['quickbooks_invoice_number_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quickbooks invoice number prefix'),
      '#description' => $this->t('Specify the prefix to the order id to be used when generating the invoice number.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('quickbooks_invoice_number_prefix'),
    ];
    $form['quickbooks_payment_reference_num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quickbooks payment reference number prefix'),
      '#description' => $this->t('Specify the prefix to the order id to be used when generating the payment reference number.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('quickbooks_payment_reference_num'),
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
      ->set('qbe.main_income_account', $form_state->getValue('main_income_account'))
      ->set('qbe.cogs_account', $form_state->getValue('cogs_account'))
      ->set('qbe.assets_account', $form_state->getValue('assets_account'))
      ->set('type_of_item_to_create_in_quickb', $form_state->getValue('type_of_item_to_create_in_quickb'))
      ->set('shipping_service', $form_state->getValue('shipping_service'))
      ->set('shipping_service_description', $form_state->getValue('shipping_service_description'))
      ->set('log_file_settings', $form_state->getValue('log_file_settings'))
      ->set('path_to_log_file', $form_state->getValue('path_to_log_file'))
      ->set('qb_pending_export', $form_state->getValue('qb_pending_export'))
      ->set('completed', $form_state->getValue('completed'))
      ->set('canceled', $form_state->getValue('canceled'))
      ->set('checkout', $form_state->getValue('checkout'))
      ->set('cart', $form_state->getValue('cart'))
      ->set('payment_status_export_trigger', $form_state->getValue('payment_status_export_trigger'))
      ->set('allow_payment_hook_exports', $form_state->getValue('allow_payment_hook_exports'))
      ->set('allow_payment_export_without_inv', $form_state->getValue('allow_payment_export_without_inv'))
      ->set('exportables', $form_state->getValue('exportables'))
      ->set('order_export_type', $form_state->getValue('order_export_type'))
      ->set('quickbooks_invoice_number_prefix', $form_state->getValue('quickbooks_invoice_number_prefix'))
      ->set('quickbooks_payment_reference_num', $form_state->getValue('quickbooks_payment_reference_num'))
      ->save();
  }

}
