<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Process plugin for node type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_parent_location_term_id",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsParentLocationTermId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $tid = empty($value) ? NULL : $this->getLocationIdByNodeId($value);
    if (!$tid) {
      throw new MigrateSkipProcessException();
    }
    return $tid;
  }

  /**
   * Gets Location Term ID by Node ID.
   *
   * @param int $nid
   *  Email Address.
   *
   * @return mixed
   *  The Term ID or NULL.
   */
  protected function getLocationIdByNodeId($nid)
  {
    $node = Node::load($nid);
    return empty($node) ? NULL : $node->get('field_location')->target_id;
  }

}
