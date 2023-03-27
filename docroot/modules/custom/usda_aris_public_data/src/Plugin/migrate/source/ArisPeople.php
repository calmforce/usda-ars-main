<?php

namespace Drupal\usda_aris_public_data\Plugin\migrate\source;

use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;
use Drupal\Core\Database\Query\Condition;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines ARIS People source plugin.
 *
 * @MigrateSource(
 *   id = "usda_aris_data_people",
 *   source_module = "usda_aris_public_data"
 * )
 */
class ArisPeople extends UsdaArsSource {

    /**
     * {@inheritdoc}
     */
    public function query() {

        $query = $this->select('V_PEOPLE_INFO_2_DIRECTORY', 'p');
        // The fields to be sent to Solr.
        $query->addExpression("[modecode_1] + '-' + [modecode_2] + '-' + [modecode_3] + '-' + [modecode_4]", 'people_modecode');
        $query->addField('p', 'PersonID', 'people_person_id');
        $query->addField('p', 'EMP_ID', 'people_employee_id');
        $query->addField('p', 'mySiteCode', 'people_location_modecode');
        $query->addField('p', 'PerFName', 'people_first_name');
        $query->addField('p', 'PerMName', 'people_middle_name');
        $query->addField('p', 'PerLName', 'people_last_name');
        $query->addField('p', 'PerCommonName', 'people_common_name');
        $query->addField('p', 'suffix', 'people_name_suffix');
        $query->addField('p', 'honorificname', 'people_honor_name');
        $query->addField('p', 'WorkingTitle', 'people_title');
        $query->addField('p', 'EMail', 'people_email');
        $query->addField('p', 'DeskPhone', 'people_phone');
        $query->addField('p', 'DeskAreaCode', 'people_phone_area_code');
        $query->addField('p', 'DeskExt', 'people_phone_ext');
        $query->addField('p', 'OfcFax', 'people_fax');
        $query->addField('p', 'OfcFaxAreaCode', 'people_fax_area_code');
        $query->addField('p', 'DeskRoomNum', 'people_room_num');
        $query->addField('p', 'DeskAddr1', 'people_address_line1');
        $query->addField('p', 'DeskAddr2', 'people_address_line2');
        $query->addField('p', 'DeskBldgAbbr', 'people_address_bldg_abbr');
        $query->addField('p', 'DeskCity', 'people_address_city');
        $query->addField('p', 'DeskState', 'people_address_state');
        $query->addField('p', 'DeskZip4', 'people_address_zip');
        $query->addField('p', 'DATE_LAST_MOD', 'people_date_last_mod');

        $or_condition = new Condition('OR');
        $or_condition->condition('p.STATUS_CODE', 'A');
        $or_condition->isNull('p.STATUS_CODE');
        $query->condition($or_condition);

        /* For consecutive imports, we shall check the time of last import
        to compare with DATE_LAST_MOD:
        use $query->isNotNull('p.DATE_LAST_MOD');
        $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
        $last_imported = $migrate_last_imported_store->get($migration->id(), FALSE);
        */

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
          'people_person_id' =>
            ['label' => $this->t('Person ID'),
             'type' => 'integer'],
          'people_employee_id' =>
            ['label' => $this->t('Employee ID'),
              'type' => 'string'],
          'people_modecode' =>
            ['label' => $this->t('Person MODECODE'),
              'type' => 'string'],
          'people_location_modecode' =>
            ['label' => $this->t('Location MODECODE'),
              'type' => 'string'],
          'people_first_name' =>
            ['label' => $this->t('First Name'),
              'type' => 'string'],
          'people_middle_name' =>
            ['label' => $this->t('Middle Name'),
              'type' => 'string'],
          'people_last_name' =>
            ['label' => $this->t('Last Name'),
              'type' => 'string'],
          'people_common_name' =>
            ['label' => $this->t('Common Name'),
              'type' => 'string'],
          'people_name_suffix' =>
            ['label' => $this->t('Name Suffix'),
              'type' => 'string'],
          'people_honor_name' =>
            ['label' => $this->t('Honorific Name'),
              'type' => 'string'],
          'people_title' =>
            ['label' => $this->t('Title'),
              'type' => 'string'],
          'people_email' =>
            ['label' => $this->t('Email'),
              'type' => 'string'],
          'people_phone' =>
            ['label' => $this->t('Phone'),
              'type' => 'string'],
          'people_phone_area_code' =>
            ['label' => $this->t('Phone Area Code'),
              'type' => 'string'],
          'people_phone_ext' =>
            ['label' => $this->t('ext.'),
              'type' => 'string'],
          'people_fax' =>
            ['label' => $this->t('Fax'),
              'type' => 'string'],
          'people_fax_area_code' =>
            ['label' => $this->t('Fax Area Code'),
              'type' => 'string'],
          'people_room_num' =>
            ['label' => $this->t('Room'),
              'type' => 'string'],
          'people_address_line1' =>
            ['label' => $this->t('Address Line 1'),
              'type' => 'string'],
          'people_address_line2' =>
            ['label' => $this->t('Address Line 2'),
              'type' => 'string'],
          'people_address_bldg_abbr' =>
            ['label' => $this->t('Building'),
              'type' => 'string'],
          'people_address_city' =>
            ['label' => $this->t('City'),
              'type' => 'string'],
          'people_address_state' =>
            ['label' => $this->t('State'),
              'type' => 'string'],
          'people_address_zip' =>
            ['label' => $this->t('Zip'),
              'type' => 'string'],
          'people_date_last_mod' =>
            ['label' => $this->t('Updated Date'),
              'type' => 'date'],
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
          'people_person_id' => [
            'type' => 'integer',
            'table_column' => 'PersonID',
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
        $migration_id = $this->migration->id();
        $person_id = $row->getSourceProperty('people_person_id');
        $people_item_id = 'aris:'. $migration_id . '/people_person_id/' . $person_id;
        $row->setSourceProperty('people_item_id', $people_item_id);
        // Updated timestamp:
        $date_last_mod = strtotime($row->getSourceProperty('people_date_last_mod'));
        if (empty($date_last_mod)) {
          $date_last_mod = time();
        }
        $row->setSourceProperty('changed', $date_last_mod);

      }
        return parent::prepareRow($row);
    }

}
