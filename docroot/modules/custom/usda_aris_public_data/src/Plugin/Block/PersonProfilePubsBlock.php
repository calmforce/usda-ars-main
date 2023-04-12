<?php

namespace Drupal\usda_aris_public_data\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
use Drupal\usda_aris_public_data\PersonProfilePubsBlockBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'USDA ARS Person Profile Pubs Block' block.
 *
 * @Block(
 *   id = "usda_person_profile_pubs_block",
 *   admin_label = @Translation("ARS Person Profile Pubs Block"),
 *   category = @Translation("USDA ARS Blocks")
 * )
 */
class PersonProfilePubsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Person Profile Pubs Block builder.
   *
   * @var PersonProfilePubsBlockBuilder
   */
  protected PersonProfilePubsBlockBuilder $personProfilePubsBlockBuilder;

  /**
   * The Person ID.
   *
   * @var int
   */
  protected int $personId = 0;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param PersonProfilePubsBlockBuilder $pubs_block_builder
   *   The Aris Authors list builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PersonProfilePubsBlockBuilder $pubs_block_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personProfilePubsBlockBuilder = $pubs_block_builder;
    // Get Node ID from path.
    $path = \Drupal::request()->getPathInfo();
    $normal_path = \Drupal::service('path_alias.manager')->getPathByAlias($path);
    if ($path_parts = explode('/', $normal_path)) {
      if ($path_parts[1] == 'node' && is_numeric($path_parts[2])) {
        $nodes = \Drupal::entityQuery('node')
          ->condition('type', 'person_profile')
          ->condition('nid', $path_parts[2])
          ->range(0, 1)
          ->execute();
        if (!empty($nodes)) {
          $node = Node::load(end($nodes));
          $person_id = $node->field_aris_person_id->value;
          if (!empty($person_id)) {
            $this->personId = (int) $person_id;
          }
        }

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('usda_aris_public_data.person_profile_pubs_block_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    return $this->personId ? $this->personProfilePubsBlockBuilder->buildPubsBlock($this->personId) : [];
  }

}
