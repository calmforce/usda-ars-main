<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsLocations migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_ars_national_programs". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_ars_national_programs",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsNationalPrograms extends UsdaArsSource {

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
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', ['NationalProgram', 'NationalProgramGroup', 'NationalProgramsPage'], 'IN');
    $query->condition('d.published', 1);
    $query->orderBy('c.nodeId', 'ASC');
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
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      $datetime = $row->getSourceProperty('updateDate');
      $timestamp = strtotime($datetime);
      $row->setSourceProperty('datetime', $timestamp);
      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
      $body = $properties['bodyText']->dataNtext;
      $htmlCode = $properties['htmlCode']->dataNtext;
      $row->setSourceProperty('body', $body);
      $row->setSourceProperty('htmlCode', $htmlCode);
      $mode_code = $properties['modeCode']->dataNvarchar;
      $row->setSourceProperty('modeCode', $mode_code);
      // Node URL, if set.
      $node_url = $properties['umbracoUrlName']->dataNvarchar;
      if (!empty($node_url)) {
        $row->setSourceProperty('node_url', $node_url);
      }
      // Node URL, if set.
      $np_code = $properties['npCode']->dataNvarchar;
      if (!empty($np_code)) {
        $row->setSourceProperty('npCode', $np_code);
      }
      // SEO fields.
      $this->addSeoProperties($properties, $row);
    }

    return parent::prepareRow($row);
  }
}
