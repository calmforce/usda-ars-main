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
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', 'PersonSite');
    // Add parent condition.
    $query->condition('pct.alias', 'PeopleFolder');
    $query->condition('d.published', 1);
    // Parent has to be published as well.
    $query->condition('pd.published', 1);
    $query->orderBy('level', 'ASC');
    //$query->condition('c.nodeId', [170963], 'IN');
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
      $created = $row->getSourceProperty('createDate');
      $created_timestamp = strtotime($created);
      $row->setSourceProperty('created', $created_timestamp);
      $changed = $row->getSourceProperty('updateDate');
      $changed_timestamp = strtotime($changed);
      $row->setSourceProperty('changed', $changed_timestamp);
      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
      // Node URL, if set.
      $node_url = $properties['umbracoUrlName']->dataNvarchar;
      if (!empty($node_url)) {
        $row->setSourceProperty('node_url', $node_url);
      }
      $body = $properties['bodyText']->dataNtext;
      if (!empty($body)) {
        $row->setSourceProperty('body', $body);
      }
      $htmlCode = $properties['htmlCode']->dataNtext;
      if (!empty($htmlCode)) {
        $row->setSourceProperty('htmlCode', $htmlCode);
      }
      $body_columned_text = $properties['bodyColumnedText']->dataNtext;
      if (!empty($body_columned_text)) {
        $row->setSourceProperty('bodyColumnedText', $body_columned_text);
      }
      // SEO fields.
      $this->addSeoProperties($properties, $row);
      // Person fields.
      $person_id = $properties['personLink']->dataNvarchar;
      if (!empty($person_id)) {
        $projects = $this->arisDbQueryService->getPersonProfileProjects($person_id);
        if (!empty($projects)) {
          $row->setSourceProperty('profileProjects', $projects);
        }
        $row->setSourceProperty('person_id', $person_id);
        // Data from ARIS DB.
        $person_properties = $this->arisDbQueryService->getPersonProperties($person_id);
        if (!empty($person_properties)) {
          $this->setPersonSourceProperties($row, $person_properties);
          if (!empty($person_properties->EmpID)) {
            $empid = $person_properties->EmpID;
            $publications = $this->arisDbQueryService->getPersonProfilePublications($empid);
            if (!empty($publications)) {
              $row->setSourceProperty('profilePublications', $publications);
            }            
          }
        }
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
