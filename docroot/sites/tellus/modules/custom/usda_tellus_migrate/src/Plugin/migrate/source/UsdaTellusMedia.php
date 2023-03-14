<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for media.
 *
 * @MigrateSource(
 *   id = "usda_tellus_media",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusMedia extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsMedia', 'm');
    $query->innerJoin('umbracoNode', 'n', 'n.id = m.nodeId');
    $query->fields('m', ['nodeId', 'mediaPath']);
    $query->addField('n', 'text', 'mediaName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path', 'nodeObjectType']);
    $query->isNotNull('m.mediaPath');
    //$query->condition('m.nodeId', [5315, 5316, 5317, 5318, 5319, 5320, 5321, 5322, 5712, 5714], 'IN');
    $query->orderBy('m.nodeId', 'DESC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uuid' => [
        'type' => 'string',
        'alias' => 'uuid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'sortOrder' => $this->t('Sort Order'),
      'createDate' => $this->t('Date Created'),
      'nodeUser' => $this->t('Node User'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      try {
        $this->umbracoDbQueryService->getDatabase('tellus');
        $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
        $caption = $properties['caption']->dataNtext;
        if (!empty($caption)) {
          $row->setSourceProperty('caption', $caption);
        }
        $media_name = $row->getSourceProperty('mediaName');
        $extension = $properties['umbracoExtension']->dataNvarchar;
        if (empty($extension)) {
          $name_array = explode('.', $media_name);
          $extension = empty($name_array[1]) ? NULL : $name_array[1];
        }
        if (empty($extension)) {
          return FALSE;
        }
        $media_type = $this->getMediaType($extension);
        if (!empty($media_type)) {
          $row->setSourceProperty('type', $media_type);
        }
        if ($media_type == 'image') {
          $alt_text = strip_tags(substr($properties['altText']->dataNtext, 0, 512));
          if (empty($alt_text)) {
            $alt_text = $media_name . ' image';
          }
          $row->setSourceProperty('image_alt', $alt_text);
          $row->setSourceProperty('image_width', $properties['umbracoWidth']->dataNvarchar);
          $row->setSourceProperty('image_height', $properties['umbracoHeight']->dataNvarchar);
        }
        $row->setSourceProperty('size', $properties['umbracoBytes']->dataNvarchar);
      } catch (\Exception $e) {
        return FALSE;
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * Gets Media Type for the file extension.
   *
   * @param string $extension
   *   The file extension.
   *
   * @return string|null
   *   The media type or NULL.
   */
  private function getMediaType(string $extension) {
    $ext_map = ['jpg' => 'image', 'png' => 'image', 'gif' => 'image', 'mp4' => 'video'];
    return empty($ext_map[$extension]) ? NULL : $ext_map[$extension];
  }

}
