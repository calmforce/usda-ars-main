<?php

namespace Drupal\usda_aris_public_data\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines ARIS Publications source plugin.
 *
 * @MigrateSource(
 *   id = "usda_aris_data_pubs",
 *   source_module = "usda_aris_public_data"
 * )
 */
class ArisPublications extends UsdaArsSource {

    /**
     * {@inheritdoc}
     */
    public function query() {
      $query = $this->select('GEN_public_115s', 'pub');
      $query->leftJoin('REF_115_PUB', 'pub_type', 'pub.PUB_TYPE_CODE = pub_type.PUB_TYPE_CODE');
      $query->leftJoin('A115_MAIN', 'main', 'pub.SEQ_NO_115 = main.SEQ_NO_115');
//      $query->addField('pub', 'SEQ_NO_115', 'pub.SEQ_NO_115');
      $query->addField('pub', 'SEQ_NO_115', 'pub_pub_id');
      $query->addField('pub', 'ACCN_NO', 'pub_project_id');
      $query->addField('pub', 'MANUSCRIPT_TITLE', 'pub_title');
      $query->addField('pub', 'citation', 'pub_citation');
      $query->addField('pub', 'DIGITAL_OBJECT_INDICATOR', 'pub_doi');
      $query->addField('pub', 'summary', 'pub_summary');
      $query->addField('pub', 'abstract', 'pub_abstract');
      $query->addField('pub', 'Journal', 'pub_journal');
      $query->addField('pub', 'JOURNAL_CODE', 'pub_journal_code');
      $query->addField('pub', 'REPRINT_URL', 'pub_url');
      $query->addField('pub', 'APPROVAL_DATE', 'pub_approval_date');
      $query->addField('pub', 'JOURNAL_ACCPT_DATE', 'pub_acceptance_date');
      $query->addField('pub', 'JOURNAL_PUB_DATE', 'pub_publication_date');
      $query->addField('pub', 'DATE_ACTIVE', 'pub_date_active');
      $query->addField('main', 'DATE_CREATED', 'pub_date_created');
      $query->addField('main', 'DATE_LAST_MOD', 'pub_date_last_mod');
      $query->addField('pub_type', 'DESCRIPTION', 'pub_type');
      $query->addExpression("RIGHT('00'+CAST(pub.[modecode_1] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(pub.[modecode_2] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(pub.[modecode_3] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(pub.[modecode_4] AS VARCHAR(2)),2)", 'pub_modecode');
      $query->where('pub.JOURNAL_ACCPT_DATE IS NOT NULL AND pub.JOURNAL_ACCPT_DATE < GETDATE()');
      $query->orderBy('pub.SEQ_NO_115', 'DESC');
      return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
          'pub_pub_id' =>
            ['label' => $this->t('Publication ID'),
              'type' => 'integer'],
          'pub_project_id' =>
            ['label' => $this->t('Publication Project ID'),
              'type' => 'integer'],
          'pub_type' =>
            ['label' => $this->t('Publication Type'),
              'type' => 'string'],
          'pub_modecode' =>
            ['label' => $this->t('Publication MODECODE'),
              'type' => 'string'],
          'pub_title' =>
            ['label' => $this->t('Publication Title'),
              'type' => 'string'],
          'pub_citation' =>
            ['label' => $this->t('Citation'),
              'type' => 'string'],
          'pub_doi' =>
            ['label' => $this->t('Digital Object Indicator'),
              'type' => 'string'],
          'pub_summary' =>
            ['label' => $this->t('Summary'),
              'type' => 'string'],
          'pub_abstract' =>
            ['label' => $this->t('Abstract'),
              'type' => 'string'],
          'pub_journal' =>
            ['label' => $this->t('Journal'),
              'type' => 'string'],
          'pub_journal_code' =>
            ['label' => $this->t('Journal Code'),
              'type' => 'string'],
          'pub_url' =>
            ['label' => $this->t('Reprint URL'),
              'type' => 'string'],
          'pub_approval_date' =>
            ['label' => $this->t('Approval Date'),
              'type' => 'date'],
          'pub_acceptance_date' =>
            ['label' => $this->t('Acceptance Date'),
              'type' => 'date'],
          'pub_publication_date' =>
            ['label' => $this->t('Publication Date'),
              'type' => 'date'],
          'pub_date_active' =>
            ['label' => $this->t('Date Active'),
              'type' => 'date'],
          'pub_date_created' =>
            ['label' => $this->t('Created Date'),
              'type' => 'date'],
          'pub_date_last_mod' =>
            ['label' => $this->t('Updated Date'),
              'type' => 'date'],
          'pub_authors' =>
            ['label' => $this->t('Publication Authors'),
              'type' => 'integer',
              'multivalued' => TRUE],
          'aris_source_plugin_id' =>
            ['label' => $this->t('ARIS Data Source Plugin ID'),
              'type' => 'string'],
        ];
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds() {
        return [
            'pub_pub_id' => [
                'type' => 'integer',
                'table_column' => 'pub.SEQ_NO_115',
            ],
        ];
    }

  /**
   * Retrieves values for multivalued fields - Authors for Pubs.
   *
   * @param int $pub_id
   * @param string $field
   *
   * @return array
   */
    public function getMultivaluedFieldValues(int $pub_id, string $field): array {
      if ($field != 'pub_authors') {
        return [];
      }
      $query = $this->select('v_AH115_Authors', 'a');
      $query->leftJoin('People', 'p', 'a.EMP_ID = p.EmpID');
      $query->addField('p', 'PersonID', 'person_id');
      $query->isNotNull('a.EMP_ID');
      $query->isNotNull('p.PersonID');
      $query->condition('a.SEQ_NO_115', $pub_id);
      $raw_data = $query->execute()->fetchAll();
      if (!empty($raw_data)) {
        $data = [];
        foreach ($raw_data as $row) {
          $data[] = $row['person_id'];
        }
        return $data;
      }
      return [];
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
        $migration_id = $this->migration->id();
        $pub_id = $row->getSourceProperty('pub_pub_id');
        $publication_item_id = 'aris:'. $migration_id . '/pub_pub_id/' . $pub_id;
        $row->setSourceProperty('publication_item_id', $publication_item_id);
        // Updated timestamp:
        $date_last_mod = strtotime($row->getSourceProperty('pub_date_last_mod'));
        if (empty($date_last_mod)) {
          $date_last_mod = time();
        }
        $row->setSourceProperty('changed', $date_last_mod);
      }
        return parent::prepareRow($row);
    }

}
