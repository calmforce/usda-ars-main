<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for media.
 *
 * @MigrateSource(
 *   id = "usda_aglab_media",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabMedia extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('umbracoMediaVersion', 'mv');
    $query->innerJoin('umbracoContentVersion', 'cv', 'cv.id = mv.id');
    $query->innerJoin('umbracoNode', 'n', 'n.id = cv.nodeId');
    $query->addField('mv', 'path', 'mediaPath');
    $query->fields('cv', ['nodeId']);
    $query->addField('n', 'text', 'mediaName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path', 'nodeObjectType']);
    $query->isNotNull('mv.path');
    // $query->condition('m.nodeId', [5289, 5371, 5377, 5426, 5277], 'IN');
    $query->orderBy('n.sortOrder', 'ASC');
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
        $this->umbracoDbQueryService->getDatabase('aglab');
        $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('nodeId'));
        $caption = strip_tags($properties['caption']->textValue, '<em>');
        if (!empty($caption)) {
          $row->setSourceProperty('caption', $caption);
        }
        $extension = $properties['umbracoExtension']->varcharValue;
        if (empty($extension)) {
          $media_path = $row->getSourceProperty('mediaPath');
          $extension = explode('.', $media_path)[1];
        }
        $media_type = $this->getMediaType($extension);
        if (!empty($media_type)) {
          $row->setSourceProperty('type', $media_type);
        }
        if ($media_type == 'image') {
          $row->setSourceProperty('image_width', $properties['umbracoWidth']->intValue);
          $row->setSourceProperty('image_height', $properties['umbracoHeight']->intValue);
          if (!empty($properties['altText']->varcharValue)) {
            $row->setSourceProperty('image_alt', $properties['altText']->varcharValue);
          }
          else {
            $row->setSourceProperty('image_alt', $row->getSourceProperty('mediaName') . ' image');
          }
        }
        $row->setSourceProperty('size', $properties['umbracoBytes']->varcharValue);
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
    $ext_map = [
      'jpg' => 'image',
      'jpeg' => 'image',
      'png' => 'image',
      'bmp' => 'image',
      'mp4' => 'video',
      'pdf' => 'document',
      ];
    return empty($ext_map[$extension]) ? NULL : $ext_map[$extension];
  }

}
