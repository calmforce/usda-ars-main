<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus Main Content in JSON format.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_node_alias",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesNodeAlias extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $parent_type = $row->getSourceProperty('parentNodeType');
    if (in_array($parent_type, ['regionalPage', 'topicPage'])) {
      try {
        if ($parent_type == 'topicPage') {
          $parent_id = $row->getDestinationProperty('topicId');
        }
        else {
          $parent_id = $row->getSourceProperty('parentID');
        }
        $parent_alias = $this->getEntityAlias($parent_id);
        if ($parent_alias) {
          // $value already has '/'.
          $value = $parent_alias . $value;
        }
      } catch (InvalidPluginDefinitionException|PluginNotFoundException|EntityMalformedException $e) {
        return NULL;
      }
    }

    return $value;
  }

  /**
   * Returns Entity alias for entity ID.
   *
   * @param int $entity_id
   *   Entity ID.
   *
   * @return string
   *   Entity alias.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException|EntityMalformedException
   */
  protected function getEntityAlias(int $entity_id): string
  {
    $path = '/taxonomy/term/' . $entity_id;
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path);
    return $alias == $path ? '' : $alias;
  }

}
