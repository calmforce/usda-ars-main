<?php

namespace Drupal\usda_ars_saml;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form altering class.
 */
class FormAlter {

  /**
   * Adds ARS ADFS link to user login form.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function alterLoginForm(&$form, FormStateInterface $form_state) {
    $saml_login_url = Url::fromRoute('usda_ars_saml.saml_login')->toString();
    $config = \Drupal::config('usda_ars_saml.settings');
    if (!$config->get('ars_saml_enable_backdoor')) {
      unset($form['name']);
      unset($form['pass']);
      unset($form['actions']);
    }
    $form['login_url']= [
      '#type' => 'markup',
      '#markup' => '<a href="'. $saml_login_url .'">Login using ARS ADFS</a>',
      '#prefix' => '<div class="adfs-login-link">',
      '#suffix' => '</div><hr>',
      '#weight' => -1000,
    ];
    \Drupal::service('page_cache_kill_switch')->trigger();
  }

}
