<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;
use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines Publications source plugin.
 *
 * @MigrateSource(
 *   id = "usda_ars_publications",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsPublications extends UsdaArsSource {

    /**
     * {@inheritdoc}
     */
    public function query() {
        $query = $this->select('GEN_public_115s', 'pub');
        $query->fields('pub');
        $query->where('pub.JOURNAL_ACCPT_DATE IS NOT NULL AND pub.JOURNAL_ACCPT_DATE < GETDATE()');
        //$query->condition('pub.SEQ_NO_115', ['126974', '145624', '323074'], 'IN');
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
            'title' => $this->t('Title'),
            'pub_id' => $this->t('Pub Id'),
            'citation' => $this->t('Citation'),
            'journal' => $this->t('Journal'),
            'doi' => $this->t('Digital Object Indicator'),
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds() {
        return [
            'SEQ_NO_115' => [
                'type' => 'integer',
                'alias' => 'pub_id',
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
        $mode_1 = $row->getSourceProperty('MODECODE_1');
        $mode_2 = $row->getSourceProperty('MODECODE_2');
        $mode_3 = $row->getSourceProperty('MODECODE_3');
        $mode_4 = $row->getSourceProperty('MODECODE_4');
        $mode_code = $mode_1 . "-" . $mode_2 . "-" . $mode_3 . "-" . $mode_4;
        $row->setSourceProperty('MODECODE', $mode_code);
      }
        return parent::prepareRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function entityTypeId() {
        return 'node';
    }

}
