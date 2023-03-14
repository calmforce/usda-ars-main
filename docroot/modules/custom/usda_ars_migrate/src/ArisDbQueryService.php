<?php

namespace Drupal\usda_ars_migrate;

/**
 * Provides methods to query the Aris MSSQL Migration DB.
 */
class ArisDbQueryService extends MssqlDbConnection {

  /**
   * Constructs the ArisDbQueryService.
   */
  public function __construct() {
    $this->connection = $this->getDatabase('aris_public_web');
  }

  /**
   * Gets ARS Location properties for the given modecode.
   *
   * @param string $node_modecode
   *   The MODECODE as string with dash separators.
   *
   * @return array|mixed
   *   NULL or empty array if failed, array of parameters is successful.
   */
  public function getLocationProperties(string $node_modecode) {
    try {

      $raw_data = [];
      $modecode = explode('-', $node_modecode);
      if (!empty($modecode)) {
        $connection = $this->getConnection();

        $query = $connection->select('REF_MODECODE', 'rmc');
        $query->addField('rmc', 'FACILITY_NAME');
        $query->addField('rmc', 'DATE_CREATED');
        $query->addField('rmc', 'USER_CREATED');
        $query->addField('rmc', 'DATE_LAST_MOD');
        $query->addField('rmc', 'USER_LAST_MOD');
        $query->addField('rmc', 'RL_EMP_ID');
        $query->addField('rmc', 'RL_EMAIL');
        $query->addField('rmc', 'RL_FAX');
        $query->addField('rmc', 'RL_TITLE');
        $query->addField('rmc', 'RL_PHONE');
        $query->addField('rmc', 'ADD_LINE_1');
        $query->addField('rmc', 'ADD_LINE_2');
        $query->addField('rmc', 'CITY');
        $query->addField('rmc', 'STATE_CODE');
        $query->addField('rmc', 'POSTAL_CODE');
        $query->addField('rmc', 'COUNTRY_CODE');
        $query->addField('rmc', 'MISSION_STATEMENT');
        $query->condition('rmc.MODECODE_1', $modecode[0]);
        $query->condition('rmc.MODECODE_2', $modecode[1]);
        $query->condition('rmc.MODECODE_3', $modecode[2]);
        $query->condition('rmc.MODECODE_4', $modecode[3]);

        $raw_data = $query->execute()->fetchAll();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
    }

    return empty($raw_data) ? $raw_data : $raw_data[0];
  }

  /**
   * Gets ARIS Person properties for the given Person ID.
   *
   * @param int $person_id
   *   The Person ID.
   *
   * @return array|mixed
   *   NULL or empty array if failed, array of parameters is successful.
   */
  public function getPersonProperties($person_id) {
    try {

      $raw_data = [];
      if (!empty($person_id)) {
        $connection = $this->getConnection();

        $query = $connection->select('people', 'p');
        $query->fields('p');
        $query->condition('p.PersonID', $person_id);
        $raw_data = $query->execute()->fetchAll();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
    }

    return empty($raw_data) ? $raw_data : $raw_data[0];
  }

   public function getPersonProfileProjects($person_id) {
    try {
      $connection = $this->getConnection();
      $query = $connection->select('w_person_projects', 'pp');      
      $query->innerJoin('w_clean_projects', 'cp', 'cp.accn_no = pp.accn_no');      
      $query->innerJoin('people', 'p', 'p.empid = pp.emp_id');
      $query->fields('pp', ['accn_no', 'prj_title']);
      $query->fields('cp', ['prj_type']);
      $query->condition('cp.PRJ_TYPE', 'J', '!=');
      $query->condition('p.PersonID', $person_id);
      $query->orderBy('cp.prj_type');
     
      $raw_data = $query->execute()->fetchAll();
     
    } catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
              array(
                  '@code' => $e->getCode(),
                  '@message' => $e->getMessage(),
      ));
    }

    $data = '';
    if(!empty($raw_data)){
       $data = json_encode($raw_data);
    }
    return $data;
  }

  public function getPersonProfilePublications($empid) {
    try {
      $connection = $this->getConnection();
      $query = $connection->select('v_AH115_Authors', 'A1A');
      $query->innerJoin('GEN_public_115s', 'v115s', 'A1A.SEQ_NO_115 = v115s.SEQ_NO_115 AND v115s.JOURNAL_ACCPT_DATE IS NOT NULL AND v115s.JOURNAL_ACCPT_DATE < GETDATE()');
      $query->fields('A1A', ['EMP_ID', 'SEQ_NO_115', 'AUTHORSHIP']);
      $query->fields('v115s', ['JOURNAL_ACCPT_DATE', 'MANUSCRIPT_TITLE', 'citation', 'PUB_TYPE_CODE', 'REPRINT_URL', 'DIGITAL_OBJECT_INDICATOR']);
      $query->condition('A1A.EMP_ID', $empid);
      $raw_data = $query->execute()->fetchAll();

    } catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
              array(
                  '@code' => $e->getCode(),
                  '@message' => $e->getMessage(),
      ));
    }

    $data = '';
    if (!empty($raw_data)) {
      $data = json_encode($raw_data);
    }
    return $data;
  }

}
