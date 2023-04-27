<?php

/**
 * @file
 * Contains \Drupal\usda_ars_saml\Routing\RouteSubscriber.
 */

namespace Drupal\usda_ars_saml\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Set admin theme on our /saml_response route.
    if ($route = $collection->get('usda_ars_saml.saml_response')) {
      $route->setOption('_admin_route', TRUE);
    }
    $config = \Drupal::config('usda_ars_saml.settings');
    if (!$config->get('ars_saml_enable_backdoor')) {
      // Change path '/user/login' to '/saml_login'.
      if ($route = $collection->get('user.login')) {
        $route->setPath('/saml_login');
      }
      // Deny access to '/user/password'.
      // Note that the second parameter of setRequirement() is a string.
      if ($route = $collection->get('user.pass')) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
