<?php

namespace Drupal\usda_aris_public_data\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\usda_aris_public_data\ArisAuthorsListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'USDA ARS Publication Authors List' block.
 *
 * @Block(
 *   id = "usda_aris_authors_list_block",
 *   admin_label = @Translation("USDA ARS Publication Authors List"),
 *   category = @Translation("USDA ARS Blocks")
 * )
 */
class ArisAuthorsListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Aris Authors list builder.
   *
   * @var ArisAuthorsListBuilder
   */
  protected ArisAuthorsListBuilder $authorsListBuilder;

  /**
   * The region term ID.
   *
   * @var int
   */
  protected int $publicationId = 0;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param ArisAuthorsListBuilder $authors_list_builder
   *   The Aris Authors list builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ArisAuthorsListBuilder $authors_list_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->authorsListBuilder = $authors_list_builder;
    // Get Publication ID from path.
    $path = \Drupal::request()->getPathInfo();
    $normal_path = \Drupal::service('path_alias.manager')->getPathByAlias($path);
    if ($path_parts = explode('/', $normal_path)) {
      if ($path_parts[1] == 'research' && $path_parts[2] == 'publications' && $path_parts[3] == 'publication' && is_numeric($path_parts[4])) {
        $this->publicationId = (int) $path_parts[4];
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
      $container->get('usda_aris_public_data.authors_list_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    return $this->publicationId ? $this->authorsListBuilder->buildAuthorsList($this->publicationId) : [];
  }

}
