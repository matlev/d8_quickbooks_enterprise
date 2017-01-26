<?php

namespace Drupal\commerce_quickbooks_enterprise\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    $help_path = Url::fromRoute('help.page', ['name' => 'commerce_quickbooks_enterprise'])->getInternalPath();

    // Get all users with the 'access quickbooks soap service' permission.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'quickbooks_user')
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
    $owner_id = \Drupal::config('commerce_quickbooks_enterprise.QuickbooksAdmin')->get('qwc_owner_id');
    $config_set = 0;

    if (empty($owner_id)) {
      // If the Owner ID is empty, then the user hasn't configured the module yet.
      // That's OK, but it means we need to generate the Owner ID here and
      // let the configuration know we've done so.
      $owner_id = $uuid->generate();
      $config_set = 1;
    }

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

    $description = $this->t('The URL of your web service.  For internal development and testing only, you can specify localhost or a machine name in place of the domain name.');
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
        : 'http://localhost/' . $qbwc_path,
      '#required' => TRUE,
    ];

    $form['app_support'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Support URL'),
      '#description' => $this->t('The support URL.  This can most likely stay unchanged, but if change is desired then the domain or machine name must match the App URL domain or machine name.'),
      '#size' => 64,
      '#default_value' => $secure
        ? $current_domain . $help_path
        : 'http://localhost/' . $help_path,
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
      '#type' => 'textfield',
      '#title' => $this->t('File ID'),
      '#description' => $this->t('An ID assigned to your Quickbooks application.  This should be left alone, but if necessary can be replaced if you have a working GUID already.'),
      '#default_value' => $file_id,
      '#maxlength' => 36,
      '#size' => 64,
      '#required' => TRUE,
    ];

    $form['owner_i_d'] = [
      '#type' => 'hidden',
      '#value' => $owner_id,
    ];

    $form['config_set'] = [
      '#type' => 'hidden',
      '#value' => $config_set,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download QWC file'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->cleanValues()->getValues();
    $properties = array();

    // Update the config if we set a new Owner ID in this form.
    $update_config = $form_values['config_set'];
    unset($form_values['config_set']);
    if ($update_config) {
      \Drupal::configFactory()
        ->getEditable('commerce_quickbooks_enterprise.QuickbooksAdmin')
        ->set('qwc_owner_id', $form_values['owner_i_d'])
        ->save();
    }

    // Convert the form name into a QWC property name. ex. app_u_r_l => AppURL.
    foreach ($form_values as $key => $value) {
      $tokens = explode('_', $key);

      $property = array_reduce($tokens, function($word, $fragment) {
        return $word . ucwords($fragment);
      });

      $properties[$property] = $value;
    }

    // Render the QWC xml twig template.
    $qwc_theme = array(
      '#theme' => 'commerce_quickbooks_enterprise_qwc',
      '#properties' => $properties,
    );
    $qwc = \Drupal::service('renderer')->render($qwc_theme, FALSE);

    // Save the generated QWC file as SERVER_HOST.qwc.
    $file = file_save_data($qwc, 'public://' . \Drupal::request()->getHost() . '.qwc');

    if ($file) {
      $uri = $file->getFileUri();

      // Automatically sets content headers and opens the file stream.
      // In order to get the full file name and attachment, we set the content
      // type to 'text/xml' and the disposition to 'attachment'.  We also delete
      // the file after sending it to ensure prying eyes don't find it later.
      $response = new BinaryFileResponse($uri, 200, ['Content-type' => 'text/xml']);
      $response->setContentDisposition('attachment');
      $response->deleteFileAfterSend(TRUE);

      $form_state->setResponse($response);
    }
    else {
      drupal_set_message(t('Unable to generate QWC file, check the log files for full details.'));
    }
  }
}