<?php
/**
 * @file
 * Contains Settings for USDA ARS SAML Service Provider Module.
 */

namespace Drupal\usda_ars_saml\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Implements the ArsSamlSettings form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ArsSamlSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usda_ars_saml_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    global $base_url;
    /**
     * Create container to hold settings form elements.
     */
    $form['usda_ars_saml_settings'] = array(
        '#type' => 'fieldset',
        '#prefix'=>'<div>ARS ADFS SETTINGS</div><hr>'
    );

    $form['usda_ars_saml_settings']['ars_saml_idp_issuer'] = array(
      '#type' => 'textfield',
      '#title' => t('IdP Entity ID or Issuer'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_idp_issuer') ?: 'https://adfs.arsnet.usda.gov/adfs/services/trust',
      '#attributes' => array('style' => 'width:90%;','placeholder' => t('IdP Entity ID or Issuer')),
      '#description' => t('<b>Note: </b>You can find the EntityID in Your IdP-Metadata XML file enclosed in <code>EntityDescriptor</code> tag having attribute as <code>entityID</code>'),
      '#prefix' =>'<div id = "ars_saml_idp_issuer">',
      '#suffix' =>'</div>',
      '#required' => TRUE,
    );

    $form['usda_ars_saml_settings']['ars_saml_idp_login_url'] = array(
      '#type' => 'url',
      '#title' => t('SAML Login URL'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_idp_login_url'),
      '#description' => t('<b>Note: </b>You can find the SAML Login URL in Your IdP-Metadata XML file enclosed in <code>SingleSignOnService</code> tag'),
      '#attributes' => array('style' => 'width:90%;', 'placeholder' => t('SAML Login URL')),
      '#prefix' =>'<div id="ars_saml_idp_login_url">',
      '#suffix' =>'</div>',
      '#required' => TRUE,
    );

    $form['usda_ars_saml_settings']['ars_saml_autocreate_users'] = array(
        '#type' => 'checkbox',
        '#title' => t('Check this option if you want to enable <b>auto creation</b> of users if user does not exist.'),
        '#description' => t("<b>Note: </b>If you disable this feature new user won't be created, only existing users can login using SSO.<br><br>"),
        '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_autocreate_users'),
    );

    $form['usda_ars_saml_settings']['ars_saml_auto_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to <b>auto redirect the user to IdP.</b>'),
      '#description' => t('<b>Note:</b> Users will be redirected to your IdP for login when the login page is accessed.<br><br>'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_auto_redirect'),
    );

    $form['usda_ars_saml_settings']['ars_saml_enable_backdoor'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to enable <b>backdoor login.</b>'),
      '#description' => t('<b>Note: </b>Checking this option <b>creates a backdoor to login to your website using Drupal credentials</b>
            in case you get locked out of your IdP. <br><br>'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_enable_backdoor'),
    );

    $form['usda_ars_saml_settings']['ars_saml_fedidcard_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to enforce the use of <b>Federal ID Card/LincPass</b>.'),
      '#description' => t('<b>Note: </b>When this option checked the users will not be authorized to login if they use Username/Password credentials.<br><br>'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_fedidcard_only'),
    );

    $form['usda_ars_saml_settings']['ars_saml_agencies'] = array(
      '#type' => 'textfield',
      '#title' => t('Agencies Authorized'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_agencies') ?: 'ARS',
      '#attributes' => array('style' => 'width:700px;','placeholder' => t('Agencies acronyms (comma-separated')),
      '#description' => t('<b>Note: </b>You can restrict access to this site by providing a comma-separated list of agencies authorized.'),
      '#prefix' =>'<div id = "ars_saml_agency">',
      '#suffix' =>'</div>',
    );

   $form['usda_ars_saml_settings']['ars_saml_login_redirect'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Redirect URL after login.'),
      '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important','placeholder' => t('Enter Default Redirect URL')),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_login_redirect'),
    );

    $form['usda_ars_saml_settings']['ars_saml_print_attributes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to <b>print out SAML response attributes.</b>'),
      '#description' => t('<b>Note:</b> If you enable this feature the redirect option <b>will be ignored.</b><br><br>'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_print_attributes'),
    );

    $form['usda_ars_saml_settings']['ars_saml_log_saml_response'] = array(
      '#type' => 'checkbox',
      '#title' => t('DEBUG: Check this option if you want to <b>log the entire SAML response XML.</b>'),
      '#description' => t('<b>Note:</b> Use this feature <b>for troubleshooting only.</b><br><br>'),
      '#default_value' => \Drupal::config('usda_ars_saml.settings')->get('ars_saml_log_saml_response'),
    );

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

  return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values        = $form_state->getValues();
    $idp_issuer         = $form_values['ars_saml_idp_issuer'];
    $idp_login_url      = $form_values['ars_saml_idp_login_url'];
    $fedidcard_only     = $form_values['ars_saml_fedidcard_only'];
    $agencies_auth      = $form_values['ars_saml_agencies'];
    $autocreate_users   = $form_values['ars_saml_autocreate_users'];
    $auto_redirect      = $form_values['ars_saml_auto_redirect'];
    $enable_backdoor    = $form_values['ars_saml_enable_backdoor'];
    $login_redirect     = $form_values['ars_saml_login_redirect'];
    $print_attributes   = $form_values['ars_saml_print_attributes'];
    $log_response_xml   = $form_values['ars_saml_log_saml_response'];
    $config = \Drupal::configFactory()->getEditable('usda_ars_saml.settings');
    $config->set('ars_saml_idp_issuer', $idp_issuer)->save();
    $config->set('ars_saml_idp_login_url', $idp_login_url)->save();
    $config->set('ars_saml_fedidcard_only', $fedidcard_only)->save();
    $config->set('ars_saml_agencies', $agencies_auth)->save();
    $config->set('ars_saml_autocreate_users', $autocreate_users)->save();
    $config->set('ars_saml_auto_redirect', $auto_redirect)->save();
    $config->set('ars_saml_enable_backdoor', $enable_backdoor)->save();
    $config->set('ars_saml_login_redirect', trim($login_redirect))->save();
    $config->set('ars_saml_print_attributes', $print_attributes)->save();
    $config->set('ars_saml_log_saml_response', $log_response_xml)->save();

    \Drupal::messenger()->addStatus(t('ARS ADFS Configuration successfully saved.'));
  }

}
