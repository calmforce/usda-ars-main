<?php

namespace Drupal\usda_aris_public_data;

use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\views\Element\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds Pubs block for given Person Profile Node.
 */
class PersonProfilePubsBlockBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildPubsBlock($person_id) {

    $build['wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="person-profile-pubs-block-container" class="grid-container">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => [
          'person-profile-pubs-block-container',
          'container',
        ],
      ],
    ];

    $view = Views::getView('aris_public_data');
    if (empty($view)) {
      return [];
    }
    // Set the argument.
    $view->setArguments([$person_id]);
    $view_build = $view->buildRenderable('person_pubs_block');

    $list = View::preRenderViewElement($view_build);
    $build['wrapper']['pubs_list'] = $list;

    return $build;
  }

}
