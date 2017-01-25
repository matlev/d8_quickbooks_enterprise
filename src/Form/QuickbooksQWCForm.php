<?php

namespace Drupal\commerce_quickbooks_enterprise\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;


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
    // Determine if we're using a secure connection, and get the domain.
    $secure = \Drupal::request()->isSecure();
    $current_domain = \Drupal::request()->getSchemeAndHttpHost();

    // Useful path storage.
    $qbwc_path = Url::fromRoute('commerce_quickbooks_enterprise.quickbooks_soap_controller')->getInternalPath();
    $help_path = URL::fromRoute('help.page.commerce_quickbooks_enterprise')->getInternalPath();

    // Get all users with the 'access quickbooks soap service' permission.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'moderator')
      ->execute();
    $users = User::loadMultiple($ids);

    $user_options = array();

    if (!empty($users)) {
      foreach($users as $user) {
        $name = $user->getAccountName();
        $user_options[$name] = $name;
      }
    }

    // Generate a FileID (GUID for Quickbooks), and load our OwnerID (GUID for server).
    $uuid = \Drupal::service('uuid');
    $file_id = $uuid->generate();

    // Finished pre-setup, create form now.
    $form['app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Name'),
      '#description' => $this->t('The name of the application visible to the user. This name is displayed in the QB web connector. It is also the name supplied in the SDK OpenConnection call to QuickBooks or QuickBooks POS'),
      '#maxlength' => 32,
      '#size' => 64,
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $description = $this->t('The URL of your web service.  For internal development and testing only, you can specify localhost or a machine name in place of the domain name.')
    $form['app_u_r_l'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App URL'),
      '#description' => $secure
        ? $description
        : $this->t('WARNING: Only local testing can be made over an insecure connection. ::: ') . $description,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $secure
        ? $current_domain . $qbwc_path
        : 'localhost' . $qbwc_path,
      '#required' => TRUE,
    ];

    $form['app_support'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Support URL'),
      '#description' => $this->t('The support URL.  This can most likely stay unchanged, but if change is desired then the domain or machine name must match the App URL domain or machine name.'),
      '#size' => 64,
      '#default_value' => $secure
        ? $current_domain . $help_path
        : 'localhost' . $help_path,
      '#required' => TRUE,
    ];

    $form['user_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Quickbooks User'),
      '#description' => $this-t("A user with specific permission to access the SOAP service calls on your site.  This list is populated by users with the 'access quickbooks soap service' permission."),
      '#options' => $user_options,
      '#required' => TRUE,
    ];

    $form['file_i_d'] = [
      '#type' => 'hidden',
      '#value' =>
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}