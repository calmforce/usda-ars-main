<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsPersonSitePages migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_ars_person_site_pages". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_ars_person_site_pages",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsPersonSitePages extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContent', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentType = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('cmsDocument', 'd', 'd.nodeId = c.nodeId');
    // Add the parent tables.
    $query->innerJoin('umbracoNode', 'pn', 'pn.id = n.parentID');
    $query->innerJoin('cmsContent', 'pc', 'pn.id = pc.nodeId');
    $query->innerJoin('cmsContentType', 'pct', 'pct.nodeId = pc.contentType');
    $query->innerJoin('cmsDocument', 'pd', 'pd.nodeId = pc.nodeId');

    $query->addField('c', 'nodeId', 'nodeId');
    $query->addField('n', 'text', 'nodeName');
    $query->addField('d', 'updateDate', 'updateDate');
    $query->addField('ct', 'alias', 'node_type');
    $query->addField('pct', 'alias', 'parent_type');
    $query->addField('pd', 'documentUser', 'nodeUser');
    $query->fields('n', ['sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', 'PersonSite');
    // Add parent condition.
    $query->condition('pct.alias', 'PeopleFolder');
    $query->condition('d.published', 1);
    // Parent has to be published as well.
    $query->condition('pd.published', 1);
    $query->orderBy('level', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'nodeName' => $this->t('Title'),
      'sortOrder' => $this->t('Sort Order'),
      'createDate' => $this->t('Date Created'),
      'updateDate' => $this->t('Date Updated'),
      'nodeUser' => $this->t('Node User'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nodeId' => [
        'type' => 'integer',
        'alias' => 'nid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      // The row has not been imported yet.
      // We have to check because the function prepareRow() called for each row
      // in each migration batch POST request.
      $node_id = $row->getSourceProperty('nodeId');
      $properties = $this->umbracoDbQueryService->getNodeProperties($node_id);
      // Person ID.
      $person_id = $properties['personLink']->dataNvarchar;
      if (!empty($person_id)) {
        $row->setSourceProperty('source_path', 'people-locations/person/' . $person_id);
        $row->setSourceProperty('redirect', 'internal:/node/' . $node_id);
      }

    }
    return parent::prepareRow($row);
  }

  /**
   * Sets person profile properties.
   *
   * @param object $row
   *   The Drupal\migrate\Row object.
   * @param object $person_properties
   *   The fields retrieved from aris_bublic_web DB.
   */
  protected function setPersonSourceProperties($row, $person_properties) {
    $mode_1 = $person_properties->mode_1;
    $mode_2 = $person_properties->mode_2;
    $mode_3 = $person_properties->mode_3;
    $mode_4 = $person_properties->mode_4;
    if ($mode_1 && $mode_2 && $mode_3 && $mode_4) {
      $mode_code = $mode_1 . "-" . $mode_2 . "-" . $mode_3 . "-" . $mode_4;
      $row->setSourceProperty('Mode_code', $mode_code);
      unset($person_properties->mode_1);
      unset($person_properties->mode_2);
      unset($person_properties->mode_3);
      unset($person_properties->mode_4);
    }
    foreach ($person_properties as $key => $value) {
      if (!empty($value)) {
        $row->setSourceProperty($key, $value);
      }
    }
  }

}
