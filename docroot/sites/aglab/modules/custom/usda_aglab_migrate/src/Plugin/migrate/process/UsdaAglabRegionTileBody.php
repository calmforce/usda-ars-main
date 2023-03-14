<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Aglab Region Tile Body.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_aglab_region_tile_body",
 *   handle_multiples = TRUE,
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabRegionTileBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($tid, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    try {
      $body = $this->getDrupalEntityFieldValue('taxonomy_term', $tid, 'body');
    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      return NULL;
    }
    return empty($body) | empty($body['value']) ? '' : $body['value'];
  }

  /**
   * Returns formated drupal-media tag for media ID.
   *
   * @param string $entity_type
   *   Entity Type ID.
   * @param int $entity_id
   *   Entity ID.
   * @param string $field_name
   *   Field machine name.
   *
   * @return array
   *   Entity field value.
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  protected function getDrupalEntityFieldValue($entity_type, $entity_id, $field_name) {
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    if ($entity) {
      $value = $entity->get($field_name)->first()->getValue();
      return $value;

    }
    return NULL;
  }
}
