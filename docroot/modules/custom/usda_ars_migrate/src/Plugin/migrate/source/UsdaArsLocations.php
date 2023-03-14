<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsLocations migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_ars_locations". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_ars_locations",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsLocations extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContent', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentType = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('cmsDocument', 'd', 'd.nodeId = c.nodeId');
    $query->addField('c', 'nodeId', 'nodeId');
    $query->addField('n', 'text', 'nodeName');
    $query->addField('d', 'updateDate', 'updateDate');
    $query->addField('ct', 'alias', 'location_type');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', ['Area', 'City', 'ResearchUnit'], 'IN');
    $query->condition('d.published', 1);
    $query->orderBy('location_type', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'nodeName' => $this->t('Area Title'),
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
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      $datetime = $row->getSourceProperty('updateDate');
      $timestamp = strtotime($datetime);
      $row->setSourceProperty('datetime', $timestamp);
      $location_type = strtolower($row->getSourceProperty('location_type'));
      $location_type = $location_type == 'researchunit' ? 'research_unit' : $location_type;
      $row->setSourceProperty('location_type', $location_type);

      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
      $mode_code = $properties['modeCode']->dataNvarchar;
      $left_custom_content = $properties['leftCustomContent']->dataNtext;
      $right_custom_content = $properties['rightCustomContent']->dataNtext;
      $row->setSourceProperty('modeCode', $mode_code);
      if (!empty($left_custom_content)) {
        $row->setSourceProperty('leftCustomContent', $left_custom_content);
      }
      if (!empty($right_custom_content)) {
        $row->setSourceProperty('rightCustomContent', $right_custom_content);
      }
      // Node URL, if set.
      $node_url = $properties['umbracoUrlName']->dataNvarchar;
      if (!empty($node_url)) {
        $row->setSourceProperty('node_url', $node_url);
      }
      // SEO fields.
      $this->addSeoProperties($properties, $row);
      // Data from ARIS DB.
      $location_properties = $this->arisDbQueryService->getLocationProperties($mode_code);
      if ($location_properties) {
        $row->setSourceProperty('phone', $location_properties->RL_PHONE);
        $row->setSourceProperty('fax', $location_properties->RL_FAX);
        $row->setSourceProperty('facility_name', $location_properties->FACILITY_NAME);
        $row->setSourceProperty('add_line_1', $location_properties->ADD_LINE_1);
        $row->setSourceProperty('add_line_2', $location_properties->ADD_LINE_2);
        $row->setSourceProperty('city', $location_properties->CITY);
        $row->setSourceProperty('state_code', $location_properties->STATE_CODE);
        $row->setSourceProperty('postal_code', $location_properties->POSTAL_CODE);
        $row->setSourceProperty('country_code', $location_properties->COUNTRY_CODE);
      }
    }

    return parent::prepareRow($row);
  }
}
