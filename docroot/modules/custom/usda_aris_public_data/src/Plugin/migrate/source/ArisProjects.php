<?php

namespace Drupal\usda_aris_public_data\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines ARIS Publications source plugin.
 *
 * @MigrateSource(
 *   id = "usda_aris_data_projects",
 *   source_module = "usda_aris_public_data"
 * )
 */
class ArisProjects extends UsdaArsSource {

    /**
     * {@inheritdoc}
     */
    public function query() {
      $query = $this->select('w_clean_projects_all', 'prj');
      $query->leftJoin('v_locations', 'loc', 'loc.modecode_1 = prj.modecode_1 AND loc.modecode_2 = prj.modecode_2 AND loc.modecode_3 = prj.modecode_3 AND loc.modecode_4 = prj.modecode_4');
      $query->leftJoin('V_PROJECT_TEAM', 't', "t.ACCN_NO = prj.ACCN_NO AND t.PRIME_INDICATOR = 'P'");
      $query->addField('prj', 'ACCN_NO', 'prj_project_id');
      $query->addField('prj', 'START_DATE', 'prj_start_date');
      $query->addField('prj', 'TERM_DATE', 'prj_end_date');
      $query->addField('prj', 'PRJ_TITLE', 'prj_title');
      $query->addField('prj', 'PRJ_TYPE', 'prj_type');
      $query->addField('prj', 'ProjectNumber', 'prj_number');
      $query->addField('prj', 'OBJECTIVE', 'prj_objective');
      $query->addField('prj', 'APPROACH', 'prj_approach');
      $query->addField('prj', 'STATUS_CODE', 'prj_status');
      $query->addField('loc', 'STATE_CODE', 'prj_state');
      $query->addField('loc', 'city', 'prj_city');
      $query->addField('t', 'PERSONID', 'prj_leader');
      $query->addExpression("RIGHT('00'+CAST(prj.[modecode_1] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(prj.[modecode_2] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(prj.[modecode_3] AS VARCHAR(2)),2) + '-' + RIGHT('00'+CAST(prj.[modecode_4] AS VARCHAR(2)),2)", 'prj_modecode');
      $query->orderBy('prj.ACCN_NO', 'DESC');
      return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
          'prj_project_id' =>
            ['label' => $this->t('Project ID'),
              'type' => 'integer'],
          'prj_type' =>
            ['label' => $this->t('Project Type'),
              'type' => 'string'],
          'prj_type_label' =>
            ['label' => $this->t('Project Type Label'),
             'type' => 'string',
             'lookup' => TRUE,
             'key_field' => 'prj_type',
            ],
          'prj_modecode' =>
            ['label' => $this->t('Project MODECODE'),
              'type' => 'string'],
          'prj_title' =>
            ['label' => $this->t('Project Title'),
              'type' => 'string'],
          'prj_number' =>
            ['label' => $this->t('Project Number'),
              'type' => 'string'],
          'prj_objective' =>
            ['label' => $this->t('Objective'),
              'type' => 'string'],
          'prj_approach' =>
            ['label' => $this->t('Approach'),
              'type' => 'string'],
          'prj_state' =>
            ['label' => $this->t('Project State'),
             'type' => 'string'],
          'prj_city' =>
            ['label' => $this->t('Project City'),
             'type' => 'string'],
          'prj_status' =>
            ['label' => $this->t('Project Status'),
              'type' => 'string'],
          'prj_status_label' =>
            ['label' => $this->t('Project Status Label'),
             'type' => 'string',
             'lookup' => TRUE,
             'key_field' => 'prj_status',
            ],
          'prj_start_date' =>
            ['label' => $this->t('Start Date'),
              'type' => 'date'],
          'prj_end_date' =>
            ['label' => $this->t('End Date'),
              'type' => 'date'],
          'prj_leader' =>
            ['label' => $this->t('Principal Investigator'),
             'type' => 'string'],
          'prj_team' =>
            ['label' => $this->t('Project Team'),
              'type' => 'integer',
              'multivalued' => TRUE],
          'prj_nat_programs' =>
            ['label' => $this->t('National Programs'),
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
            'prj_project_id' => [
                'type' => 'integer',
                'table_column' => 'prj.ACCN_NO',
            ],
        ];
    }

  /**
   * Retrieves values for multivalued fields.
   *
   * @param int $prj_id
   * @param string $field
   *
   * @return array
   */
    public function getMultivaluedFieldValues(int $prj_id, string $field): array {
      if (!in_array($field, ['prj_team', 'prj_nat_programs'])) {
        return [];
      }
      if ($field == 'prj_team') {
        return $this->getProjectTeam($prj_id);
      }
      else {
        return $this->getNatPrograms($prj_id);
      }
    }

  /**
   * @param int $prj_id
   *   The Project ID.
   *
   * @return array
   *   The project team Person IDs.
   */
    private function getProjectTeam(int $prj_id): array {
      $query = $this->select('V_PROJECT_TEAM', 'pt');
      $query->addField('pt', 'PERSONID', 'person_id');
      $query->condition('pt.ACCN_NO', $prj_id);
      $query->orderBy('PRIME_INDICATOR', 'DESC');
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
   * Gets National Programs for Project ID.
   *
   * @param int $prj_id
   *   The Project ID.
   *
   * @return array
   *   The national programs.
   */
  private function getNatPrograms(int $prj_id): array {
    $query = $this->select('w_clean_projects_all', 'prj');
    $query->addField('prj', 'NP_Number1', 'prj_np_1');
    $query->addField('prj', 'NP_Number2', 'prj_np_2');
    $query->condition('prj.ACCN_NO', $prj_id);
    $raw_data = $query->execute()->fetchAll();
    $data = [];
    foreach ($raw_data as $row) {
      if (!empty($row['prj_np_1'])) {
        $data[] = $row['prj_np_1'];
      }
      if (!empty($row['prj_np_2'])) {
        $data[] = $row['prj_np_2'];
      }
    }
    return $data;
  }

  /**
   * Retrieves values for LookUp fields.
   *
   * @param string $field
   *   The field name.
   *
   * @return array
   *   The map.
   */
  public function getLookUpFieldMap(string $field): array {
    if (!in_array($field, ['prj_status_label', 'prj_type_label'])) {
      return [];
    }
    if ($field == 'prj_status_label') {
      return [
        'A' => 'Active',
        'E' => 'Terminated',
        'X' => 'Expired',
      ];
    }
    else {
      return [
        'A' => 'Cooperative Agreement',
        'B' => 'Standard Cooperative Agreement',
        'C' => 'Cooperative Research and Development Agreement',
        'D' => 'In-House Appropriated',
        'G' => 'Grant',
        'H' => 'Material Transfer Research Agreement',
        'I' => 'Interagency Reimbursable Agreement',
        'J' => 'Research Support Agreement',
        'L' => 'Cross Location',
        'M' => 'Memorandum of Understanding',
        'N' => 'Non-Funded Cooperative Agreement',
        'O' => 'Outgoing Interagency Agreement',
        'P' => 'PL-480 Agreement',
        'Q' => 'General Cooperative Agreement',
        'R' => 'Reimbursable Cooperative Agreement',
        'S' => 'Non-Assistance Cooperative Agreement',
        'T' => 'Trust Fund Cooperative Agreement',
        'X' => 'Other',
        'Y' => 'Contract',
      ];
    }
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
        $prj_id = $row->getSourceProperty('prj_project_id');
        $project_item_id = 'aris:'. $migration_id . '/prj_project_id/' . $prj_id;
        $row->setSourceProperty('project_item_id', $project_item_id);
        // Updated timestamp:
        $date_last_mod = time();
        $row->setSourceProperty('changed', $date_last_mod);
      }
        return parent::prepareRow($row);
    }

}
