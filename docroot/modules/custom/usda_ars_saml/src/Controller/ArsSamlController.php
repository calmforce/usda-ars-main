<?php

/**
 * @file
 * Contains \Drupal\usda_ars_saml\Controller\ArsSamlController.
 */

namespace Drupal\usda_ars_saml\Controller;

use DOMDocument;
use DOMXPath;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\usda_ars_saml\Utilities;
use Drupal\usda_ars_saml\Saml2Response;

/**
 * Default controller for the usda_ars_saml module.
 */
class ArsSamlController extends ControllerBase {

  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilder $formBuilder = NULL){
      $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
      return new static(
          $container->get("form_builder")
      );
  }

  /**
   * Processes SAML 1.0 response from ARS ADFS server.
   *
   * @return array|array[]|void
   *   The render array.
   */
  public function saml_response() {

    if (empty($_POST)) {
      \Drupal::messenger()->addMessage($this->t('No body in _$POST found'), 'error');
      return [];
    }
    $config = \Drupal::config('usda_ars_saml.settings');
    $response = $this->processPostBody($_POST);

    $print_attributes = $config->get('ars_saml_print_attributes');
    if ($print_attributes) {
      $render_array = $this->getDebugRenderArray($response);
    }
    else {
      $render_array = [];
    }
    // Check do we have to enforce FedID Card.
    $fedid_only = $config->get('ars_saml_fedidcard_only');
    if (!empty($fedid_only)) {
      $upn = explode('@', $response['upn']);
      if (strtolower($upn[1]) != 'fedidcard.gov') {
        \Drupal::messenger()
          ->addMessage($this->t('Please use your Federal ID Card to login, Username/Password are not authorized.'), 'error');
        return $render_array;
      }
    }
    if (!empty($agencies_auth = $config->get('ars_saml_agencies'))) {
      $user_agency = explode(':', $response['agency'])[1];
      $agencies_auth = explode(',', $agencies_auth);
      $user_authorized = FALSE;
      foreach ($agencies_auth as $agency) {
        if(strcasecmp($user_agency, trim($agency)) == 0) {
          $user_authorized = TRUE;
          break;
        }
      }
      if (!$user_authorized) {
        \Drupal::messenger()
          ->addMessage($this->t('Unable to login user from unauthorized agency.'), 'error');
        return $render_array;
      }
    }

    if (!empty($response['email'])) {
      $email = $response['email'];
      $account = user_load_by_mail($email);
    }
    else {
      return $render_array;
    }

    if (empty($account) && !empty($response['email']) && $config->get('ars_saml_autocreate_users')) {
      // Check do we have an account with the same name.
      $name = $response['name'];
      if (user_load_by_name($name)) {
        $name = $response['email'];
      }
      $random_password = \Drupal::service('password_generator')->generate();

      $new_user = [
        'name' => $name,
        'mail' => $response['email'],
        'pass' => $random_password,
        'status' => 1,
      ];
      // Create and save the user entity.
      try {
        $account = User::create($new_user);
        $account->save();
      }
      catch (\Exception $e) {
        $error_message = $e->getMessage();
        \Drupal::messenger()->addMessage($this->t('Unable to create account with name @username and email @email, error: @error.', [
          '@username' => $response['name'],
          '@email' => $email,
          '@error' => $error_message,
        ]), 'error');
        return $render_array;
      }
    }
    if (!empty($account)) {
      $username = $account->getAccountName();
      if (!user_is_blocked($username)) {
        user_login_finalize($account);
        \Drupal::messenger()
          ->addMessage($this->t('User @username successfully authenticated.', ['@username' => $username]));

        if (!$print_attributes && !empty($config->get('ars_saml_login_redirect'))) {
          global $base_url;
          $redirectUrl = $base_url . $config->get('ars_saml_login_redirect');
          $response = new RedirectResponse($redirectUrl);
          $request = \Drupal::request();
          $request->getSession()->save();
          $response->prepare($request);
          \Drupal::service('kernel')->terminate($request, $response);
          $response->send();
          exit();
        }
        else {
          return $render_array;
        }
      }
      else {
        \Drupal::messenger()
          ->addMessage($this->t('User account blocked, not allowed to login. Please Contact your administrator.'), 'error');
        return $render_array;
      }
    }
    else {
      // No user account found or created.
      \Drupal::messenger()
        ->addMessage($this->t('No account created or found.'), 'error');
      return $render_array;
    }

  }

  private function getDebugRenderArray($response) {
    return [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $this->t('SAML Response'),
      ],
      'email_title' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->t('Email') . ': ' . (!empty($response['email']) ? $response['email'] : $this->t('No email address found')),
      ],
      'name_title' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' =>  $this->t('Name') . ': ' . (!empty($response['name']) ? $response['name'] : $this->t('No name found')),
      ],
      'attributes_html' => [
        '#type' => 'markup',
        '#markup' => !empty($response['attr_table']) ? $response['attr_table'] : $this->t('No attributes found'),
      ],
    ];
  }

  /**
   * Provides SSO login functionality.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function saml_login() {
    $config = \Drupal::config('usda_ars_saml.settings');
    $base_url = Utilities::getBaseUrl();
    $saml_login_url = $base_url . '/saml_login';
    $relay_state = $_SERVER['HTTP_REFERER'];

    if (empty($relay_state) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
      $pre = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
      $url = $pre . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
      $relay_state = $url;
    }
    if (empty($relay_state) || $relay_state==$saml_login_url) {
      $relay_state = $base_url;
    }
    $acs_url = Utilities::getAcsUrl();
    $sso_url = $config->get('ars_saml_idp_login_url');
    $nameid_format = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
    $redirect = $this->initiateLogin( $acs_url, $sso_url, $base_url, $nameid_format, $relay_state );
    $response = new RedirectResponse( $redirect );
    $response->send();
    return new Response();
  }

  /**
   * Processes _$POST request content body.
   *
   * @param $post
   *
   * @return array
   */
  protected function processPostBody($post) {
    $response = [];

    if (!empty($post['wa']) && $post['wa'] == 'wsignin1.0' && !empty($post['wresult'])) {
      $response =  $this->processSaml1Response($post['wresult']);
    }
    else if (!empty($post['SAMLResponse'])) {
      $response =  $this->processSaml2Response($post['SAMLResponse']);
    }

    return $response;

  }

  /**
   * Extracts some attributes from SAML 1.0 response.
   *
   * @param $saml_response
   *   SAML 1.0 XML response.
   *
   * @return array
   *   The response attributes array.
   */
  protected function processSaml1Response($saml_response) {

    $document = new DOMDocument();
    $document->loadXML($saml_response);
    $doc = $document->documentElement;
    $xpath = new DOMXpath($document);
    $xpath->registerNamespace('t', 'http://schemas.xmlsoap.org/ws/2005/02/trust');
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:1.0:assertion');

    $attributes = $xpath->query('/t:RequestSecurityTokenResponse/t:RequestedSecurityToken/saml:Assertion/saml:AttributeStatement/saml:Attribute', $doc);
    $email_node = $xpath->query('/t:RequestSecurityTokenResponse/t:RequestedSecurityToken/saml:Assertion/saml:AttributeStatement/saml:Attribute[@AttributeName="emailaddress"]', $doc);
    $name_node = $xpath->query('/t:RequestSecurityTokenResponse/t:RequestedSecurityToken/saml:Assertion/saml:AttributeStatement/saml:Attribute[@AttributeName="name"]', $doc);
    $uuid_node = $xpath->query('/t:RequestSecurityTokenResponse/t:RequestedSecurityToken/saml:Assertion/saml:AttributeStatement/saml:Attribute[@AttributeName="privatepersonalidentifier"]', $doc);

    $response = [];

    if (!empty($email_node->count())) {
      $response['email'] = $email_node->item(0)->firstChild->nodeValue;

    }
    if (!empty($name_node->count())) {
      $response['name'] = $name_node->item(0)->firstChild->nodeValue;
    }
    if (!empty($uuid_node->count())) {
      $response['uuid'] = $uuid_node->item(0)->firstChild->nodeValue;
    }
    $response['attr_table'] = $document->saveHTML();
    $response['attributes'] = $attributes;

    return $response;
  }

  /**
   * @param $acs_url
   * @param $sso_url
   * @param $issuer
   * @param $nameid_format
   * @param $relay_state
   *
   * @return string
   */
  protected function initiateLogin($acs_url, $sso_url, $issuer, $nameid_format, $relay_state) {

    if ($relay_state=="displaySAMLRequest") {
      $saml_request = Utilities::createAuthnRequest($acs_url,$issuer,$nameid_format,FALSE,TRUE);
      Utilities::Print_SAML_Request($saml_request,$relay_state);
    }
    else {
      $saml_request = Utilities::createAuthnRequest($acs_url, $issuer, $nameid_format);
    }

    if (strpos($sso_url, '?') > 0) {
      $redirect = $sso_url . '&SAMLRequest=' . $saml_request . '&RelayState=' . urlencode($relay_state);
    }
    else {
      $redirect = $sso_url . '?SAMLRequest=' . $saml_request . '&RelayState=' . urlencode($relay_state);
    }
    return($redirect);
  }

  /**
   * Extracts some attributes from SAML 2.0 response.
   *
   * @param $saml_response
   *   SAML 1.0 XML response.
   *
   * @return array
   *   The response attributes array.
   */
  protected function processSaml2Response($saml_response_base64): array {

    $saml_response_string = base64_decode($saml_response_base64);
    $document = new DOMDocument();
    $document->loadXML($saml_response_string);
    $saml_response_xml = $document->firstChild;

    $doc = $document->documentElement;
    $xpath = new DOMXpath($document);
    $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

    // Create an XML security object.
    try {
      $saml_2_response = new Saml2Response($saml_response_xml);
    }
    catch (\Exception $e) {
      return [];
    }

    $response_signature_data = $saml_2_response->getSignatureData();
    $assertion_signature_data = current($saml_2_response->getAssertions())->getSignatureData();
    if ( is_null( $response_signature_data ) && is_null( $assertion_signature_data ) ) {
      \Drupal::messenger()
          ->addMessage($this->t('SAML 2.0 Response: Neither response nor assertion is signed.'), 'error');
      return [];
    }

    if (!Utilities::validateIssuerAndAudience($saml_2_response, Utilities::getIssuer(), Utilities::getBaseUrl())) {
      \Drupal::messenger()
        ->addMessage($this->t('Unable to validate Issuer and Audience.'), 'error');
    }

    $attributes = current($saml_2_response->getAssertions())->getAttributes();

    $attr_table = Utilities::printAttributes($attributes);

    $response = [];

    if (!empty($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'])) {
      $response['email'] = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0];
    }
    if (!empty($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'])) {
      $response['name'] = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'][0];
    }
    if (!empty($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/privatepersonalidentifier'])) {
      $response['uuid'] = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/privatepersonalidentifier'][0];
    }
    if (!empty($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/upn'])) {
      $response['upn'] = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/upn'][0];
    }
    if (!empty($attributes['http://schemas.usda.gov/ws/2015/12/identity/claims/agencyabbr'])) {
      $response['agency'] = $attributes['http://schemas.usda.gov/ws/2015/12/identity/claims/agencyabbr'][0];
    }
    $response['attr_table'] = $attr_table;

    $response['response_json'] = json_encode($response);

    return $response;

  }

}
